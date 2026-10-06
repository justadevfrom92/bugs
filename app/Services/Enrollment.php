<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\PlanTerm;
use App\Models\QueueLog;
use App\Models\ServiceAddress;
use App\Models\User;
use App\Models\WorkItem;
use App\Models\ZipRange;
use Illuminate\Support\Facades\DB;

/**
 * Creates a new order (customer account). Used by Corral → Create Order and by
 * the website sign-up, so both make orders the same way.
 */
class Enrollment
{
    /** Market for a zip code, from the zip ranges in the database. */
    public static function marketForZip(string $zip): ?int
    {
        $z = (int) $zip;

        return ZipRange::where('zip_from', '<=', $z)->where('zip_to', '>=', $z)->value('market_id');
    }

    /**
     * @param  array  $d  name, phone, email, address, city, zip, market_id, plan_id, enrollment_type, start_date,
     *                    optional: first_name, last_name, unit, esiid, biz/business type, autopay, paperless, peak_perks,
     *                    phone_type, language, msid, channel, promo_code, ip, referred_by, username, password
     */
    public static function create(array $d, string $source, ?User $by = null, ?string $note = null): Customer
    {
        return DB::transaction(function () use ($d, $source, $by, $note) {
            $plan = Plan::findOrFail($d['plan_id']);
            $parts = explode(' ', trim($d['name']), 2);
            $c = Customer::create([
                'account' => (string) ((int) Customer::max('account') + 1373),
                'ticket' => now()->format('YmdHisv').random_int(100, 999),
                'name' => $d['name'], 'first_name' => $d['first_name'] ?? $parts[0], 'last_name' => $d['last_name'] ?? ($parts[1] ?? ''),
                'type' => ! empty($d['biz']) ? 'Small Business' : 'Residential',
                'phone' => $d['phone'], 'phone_type' => $d['phone_type'] ?? null, 'email' => $d['email'], 'language' => $d['language'] ?? 'English',
                'address' => $d['address'], 'unit' => $d['unit'] ?? null, 'city' => $d['city'], 'zip' => $d['zip'],
                'billing_street' => $d['address'], 'billing_city' => $d['city'], 'billing_state' => 'TX', 'billing_zip' => $d['zip'],
                'market_id' => $d['market_id'], 'esiid' => ($d['esiid'] ?? null) ?: null, 'plan_id' => $plan->id,
                'status' => 'Submitted', 'exception' => empty($d['esiid']) ? 'No ESIID' : null,
                'source' => $source, 'enrollment_type' => $d['enrollment_type'],
                'move_switch' => $d['enrollment_type'] === 'Move-In' ? 'move' : 'switch',
                'start_date' => $d['start_date'] ?? null, 'requested_start' => $d['start_date'] ?? null,
                'autopay' => ! empty($d['autopay']), 'paperless' => ! empty($d['paperless']), 'peak_perks' => ! empty($d['peak_perks']),
                'channel' => $d['channel'] ?? $source, 'msid' => $d['msid'] ?? ($source === 'Website' ? '1001' : '2001'), 'promo_code' => $d['promo_code'] ?? null,
                'ip' => $d['ip'] ?? null, 'referred_by' => $d['referred_by'] ?? null, 'marketing_opt_in' => ! empty($d['marketing_opt_in']),
                'username' => $d['username'] ?? null, 'password' => $d['password'] ?? null,
                'rate_class' => now()->format('Ymd').'_'.($plan->term === 1 ? 'V' : 'F').'_'.$plan->internal,
                'status_info' => 'New',
            ]);
            $c->update(['referral_code' => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $c->first_name), 0, 4)).substr($c->account, -4)]);

            // The price the customer signed up at: today's rate and TDSP fee for their market
            $catalog = app(Catalog::class);
            $rate = $catalog->currentRates()[$plan->id.'-'.$d['market_id']] ?? null;
            $fee = $catalog->currentFees()[$d['market_id']] ?? null;
            PlanTerm::create(['customer_id' => $c->id, 'plan_id' => $plan->id, 'rate_class' => $c->rate_class, 'status' => 'pending', 'ordered_at' => now(),
                'contract_start' => $d['start_date'] ?? null, 'contract_end' => isset($d['start_date']) && $plan->term > 1 ? now()->parse($d['start_date'])->addMonths($plan->term) : null,
                'energy_charge' => $rate ? round($rate->energy / 100, 8) : 0,
                'rate_2000' => $rate && $fee ? round(Catalog::averagePrice($rate->energy, $fee->per_kwh, $fee->per_bill, $plan->mrc, 2000), 1) : 0]);
            ServiceAddress::create(['customer_id' => $c->id, 'street' => trim($d['address'].' '.($d['unit'] ?? '')), 'city' => $d['city'], 'zip' => $d['zip'],
                'esiid' => ($d['esiid'] ?? null) ?: null, 'ordered_at' => now(), 'service_start' => $d['start_date'] ?? null]);
            QueueLog::create(['customer_id' => $c->id, 'queue' => 'QueueCartSubmitted', 'entered_at' => now(), 'user_id' => $by?->id]);
            $c->notes()->create(['user_id' => $by?->id, 'author' => $by?->name ?? 'System',
                'body' => $note ?? 'Enrollment submitted via '.$source.'.']);
            WorkItem::create(['queue' => 'unprocessed-orders', 'customer_id' => $c->id, 'summary' => 'Send enrollment to the utility']);

            return $c;
        });
    }
}
