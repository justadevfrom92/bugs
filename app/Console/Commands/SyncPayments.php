<?php

namespace App\Console\Commands;

class SyncPayments extends TrackedCommand
{
    protected $signature = 'et:sync-payments {--user= : ID of the admin who ran it}';

    protected $description = 'Sync payments to Utilibill';

    protected function work(): array
    {
        return $this->needsIntegration('utilibill', 'Sync payments to Utilibill');
    }
}
