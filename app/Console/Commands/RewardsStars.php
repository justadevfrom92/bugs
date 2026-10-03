<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\JobRun;

/** Daily Rangler Rewards: 10 stars per on-flow account, plus 5 for AutoPay. Runs at most once a day. */
class RewardsStars extends TrackedCommand
{
    protected $signature = 'et:rewards-stars {--user= : ID of the admin who ran it}';

    protected $description = 'Award daily rewards stars to on-flow customers';

    protected function work(): array
    {
        $already = JobRun::where('command', $this->getName())->where('status', 'OK')->whereDate('started_at', today())->exists();
        if ($already) {
            return ['Skipped', 'Stars were already awarded today'];
        }

        $onFlow = Customer::where('status', 'Good - On Flow');
        $count = (clone $onFlow)->count();
        (clone $onFlow)->increment('stars', 10);
        (clone $onFlow)->where('autopay', true)->increment('stars', 5);

        return ['OK', 'Awarded stars to '.$count.' accounts'];
    }
}
