<?php

namespace App\Console\Commands;

class SendOrders extends TrackedCommand
{
    protected $signature = 'et:send-orders {--user= : ID of the admin who ran it}';

    protected $description = 'Send submitted orders to the utility';

    protected function work(): array
    {
        return $this->needsIntegration('utilibill', 'Send submitted orders to the utility');
    }
}
