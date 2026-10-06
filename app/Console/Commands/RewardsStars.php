<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\RewardRule;
use App\Models\StarEntry;

/**
 * Awards stars by the earning rules in Bounty → Earning Rules. Each award is a
 * star history row with a reason that includes what it was for, so running
 * again never awards the same thing twice.
 */
class RewardsStars extends TrackedCommand
{
    protected $signature = 'et:rewards-stars {--user= : ID of the admin who ran it}';

    protected $description = 'Award rewards stars using the earning rules';

    protected function work(): array
    {
        $awarded = 0;
        $month = now()->format('Y-m');
        $give = function (Customer $c, string $reason, float $stars) use (&$awarded) {
            if ($stars > 0 && ! StarEntry::where('customer_id', $c->id)->where('reason', $reason)->exists()) {
                StarEntry::create(['customer_id' => $c->id, 'reason' => $reason, 'stars' => round($stars, 1)]);
                $awarded++;
            }
        };

        $onFlow = Customer::where('status', 'Good - On Flow')->get();
        if ($rule = RewardRule::stars('loyalty_monthly')) {
            $onFlow->each(fn ($c) => $give($c, $rule->label.' '.$month, $rule->stars));
        }
        if ($rule = RewardRule::stars('autopay_monthly')) {
            $onFlow->where('autopay', true)->each(fn ($c) => $give($c, $rule->label.' '.$month, $rule->stars));
        }
        if ($rule = RewardRule::stars('on_time_payment')) {
            Payment::with('customer')->where('status', 'Success')->where('paid_on', '>=', today()->subDays(45))->get()
                ->each(fn ($p) => $p->customer && $give($p->customer, $rule->label.' '.$p->reference, $rule->per_dollar ? $p->amount * $rule->stars : $rule->stars));
        }
        if ($rule = RewardRule::stars('paperless_signup')) {
            Customer::where('paperless', true)->get()->each(fn ($c) => $give($c, $rule->label, $rule->stars));
        }
        if ($rule = RewardRule::stars('referral')) {
            Customer::whereNotNull('referred_by')->where('status', 'Good - On Flow')->get()->each(function ($friend) use ($give, $rule) {
                $referrer = Customer::where('referral_code', $friend->referred_by)->first();
                $referrer && $give($referrer, $rule->label.' '.$friend->account, $rule->stars);
            });
        }

        Customer::whereIn('id', StarEntry::where('created_at', '>=', now()->subMinutes(5))->pluck('customer_id'))->get()->each->syncStars();

        return ['OK', 'Awarded '.$awarded.' star entries'];
    }
}
