<?php

namespace Database\Seeders;

use App\Models\ContactLog;
use App\Models\Customer;
use App\Models\DataItem;
use App\Services\Products;
use Illuminate\Database\Seeder;

/**
 * Fictional records for the original Corral item models the rest of the seed
 * data doesn't cover: attributes, content viewed, devices, reviews, forecast
 * models, usage buckets, the welcome/rewards/AutoPay emails and a few more products.
 */
class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Customer::orderBy('id')->limit(12)->get();
        foreach ($customers as $i => $c) {
            // Never later than now: new accounts get their later items squeezed into the time they have had
            $at = fn (int $days) => $c->created_at->copy()->addDays($days)->min(now()->subHours(1 + $days % 7));
            $item = fn (string $model, string $summary, array $data, int $days) => DataItem::create(['customer_id' => $c->id, 'model' => $model, 'summary' => $summary, 'data' => $data,
                'created_at' => $at($days), 'updated_at' => $at($days)]);

            foreach ([['pool', ['y', 'n'][$i % 2]], ['ev', ['n', 'y', 'n'][$i % 3]], ['home_type', ['Single family', 'Apartment', 'Townhome'][$i % 3]], ['sqft', (string) (1400 + $i * 175)]] as $n => [$name, $value]) {
                $item('ItemAttribute_model', "$name = $value", ['name' => $name, 'value_text' => is_numeric($value) ? '' : $value, 'value_int' => is_numeric($value) ? $value : '',
                    'value_decimal' => '', 'value_date' => '', 'start_unix' => (string) $at(1 + $n)->timestamp, 'end_unix' => ''], 1 + $n);
            }
            $item('ItemContentViewed_model', 'Peak Perks banner', ['content_id' => (string) (40 + $i), 'times_shown' => (string) (3 + $i), 'last_shown' => $at(20)->toDateTimeString(),
                'times_clicked' => (string) ($i % 3), 'last_clicked' => $i % 3 ? $at(18)->toDateTimeString() : ''], 20);
            if ($i % 2 === 0) {
                $item('ItemDevice_model', ['iOS', 'Android'][$i % 4 === 0 ? 0 : 1].' app', ['type' => $i % 4 === 0 ? 'ios' : 'android', 'uuid' => sprintf('%08X-0000-4000-8000-%012d', 4096 + $i, $c->id),
                    'logins' => (string) (2 + $i), 'login_date' => $at(25)->toDateTimeString(), 'logout_date' => $at(25)->addHour()->toDateTimeString(), 'valid' => 'y',
                    'allow_notifications' => $i % 4 === 0 ? 'y' : 'n', 'app_version' => '3.4.'.($i % 5)], 25);
            }
            if ($i % 3 === 0) {
                $item('ItemReview_model', 'Review request', ['campaign_id' => (string) (900 + $i), 'email' => (string) $c->email, 'source' => ['Google', 'Trustpilot'][$i % 2], 'sent' => 'y'], 35);
            }
            $item('ItemForecastModel_model', 'Hourly forecast', ['uan1' => (string) $c->esiid, 'zip' => (string) $c->zip, 'station' => ['KIAH', 'KDFW', 'KAUS'][$i % 3],
                'profile' => (string) ($c->load_profile ?: 'RESHIWR_COAST_IDR_WS_NOTOU'), 'model' => 'regression-v2', 'scalar' => number_format(0.95 + $i / 100, 2),
                'adjustments' => '{"hvac":1.10,"pool":'.($i % 2 ? '1.00' : '1.05').'}', 'detail_level' => 'hourly', 'last_calculated' => $at(30)->toDateTimeString()], 30);
            foreach (['summer' => 1.35, 'winter' => 0.85] as $season => $f) {
                $hours = [];
                foreach (range(1, 24) as $h) {
                    $hours[sprintf('he%02d', $h)] = number_format((0.6 + 0.9 * max(0, sin(($h - 6) / 24 * M_PI * 2))) * $f * (1 + $i / 20), 2, '.', '');
                }
                $total = array_sum($hours) * 30;
                $item('ItemBucketUsage_model', ucfirst($season).' buckets', ['uan1' => (string) $c->esiid, 'uan2' => '', 'uan3' => '', 'market_id' => (string) $c->market_id, 'season' => $season,
                    'he01_he06' => number_format(array_sum(array_slice($hours, 0, 6)) * 30, 1, '.', ''), 'he07_he12' => number_format(array_sum(array_slice($hours, 6, 6)) * 30, 1, '.', ''),
                    'he13_he18' => number_format(array_sum(array_slice($hours, 12, 6)) * 30, 1, '.', ''), 'he19_he24' => number_format(array_sum(array_slice($hours, 18, 6)) * 30, 1, '.', ''),
                    'onpeak' => number_format(array_sum(array_slice($hours, 13, 7)) * 30, 1, '.', ''), 'offpeak' => number_format(($total / 30 - array_sum(array_slice($hours, 13, 7))) * 30, 1, '.', ''),
                    ...$hours, 'total' => number_format($total, 1, '.', ''), 'days' => '30', 'bucket_name' => $total > 1200 ? 'High Avg' : 'Standard', 'crunch_date' => $at(32)->toDateString(), 'locked' => 'n'], 32);
            }

            // The welcome, accepted, rewards and AutoPay-removed emails
            foreach ([['Order Accepted', 0], ['Welcome Letter', 2], ['Rewards - Welcome', 3], ['Rewards - Stars Earned', 40], ['Renewal Offer', 50], ['AutoPay Removed', 55], ['Custom Email', 60]] as $n => [$tpl, $days]) {
                if ($tpl === 'AutoPay Removed' && $i % 3) {
                    continue;
                }
                if (in_array($tpl, ['Welcome Letter', 'Renewal Offer'], true) && $c->contactLogs()->where('template', $tpl)->exists()) {
                    continue;
                }
                ContactLog::create(['customer_id' => $c->id, 'channel' => 'Email', 'template' => $tpl, 'body' => "<p>Hi {$c->first_name}, this is the {$tpl} email.</p>",
                    'status' => 'sent', 'sent_at' => $at($days), 'opened_at' => $n % 2 ? $at($days)->addHours(3) : null, 'clicked_at' => $n === 3 ? $at($days)->addHours(4) : null,
                    'created_at' => $at($days), 'updated_at' => $at($days)]);
            }
        }

        // Products the other seed data doesn't have, with their own fields
        // (not the demo My Account login, whose self-service tests start from a known set)
        $demo = $customers->firstWhere('account', '!=', '1219000000');
        $more = [
            'Peak Perks' => ['price' => '0.00', 'description' => 'Summer peak-time savings events'],
            'Peak Perks+' => ['price' => '0.00', 'description' => 'Peak Perks with a smart thermostat'],
            'ecobee' => ['price' => '0.00', 'tracking_number' => '1Z999AA10123456784', 'serial_number' => 'ECB-TEST-0001', 'ship_date' => now()->subMonths(2)->toDateString(), 'sent' => 'y', 'connected' => 'y'],
            'Solar Lead Referral' => ['price' => '0.00', 'description' => 'Referred to a solar installer'],
            'Electric Vehicle Charger Protection Program' => ['price' => '7.99', 'total_price' => '7.99', 'aig_code' => 'EVC', 'status' => 'active', 'coverage_start' => now()->subMonth()->toDateString()],
            'Electric System Protection Program' => ['price' => '9.99', 'total_price' => '9.99', 'aig_code' => 'ESP', 'status' => 'active', 'coverage_start' => now()->subMonth()->toDateString()],
        ];
        foreach ($more as $product => $data) {
            if ($demo && Products::add($demo, $product)) {
                $demo->products()->where('product', $product)->whereNull('removed_at')->latest('id')->first()?->update(['data' => $data + ['source' => 'Corral']]);
            }
        }
    }
}
