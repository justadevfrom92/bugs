<?php

use Illuminate\Support\Facades\Schedule;

/*
| Scheduled jobs come from config/admin.php (Sheriff → Crons shows them).
| On the Linux server add one cron entry:
|   * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
*/
foreach (config('admin.jobs') as $command => [$name, $cron]) {
    Schedule::command($command)->cron($cron)->withoutOverlapping()->description($name);
}
