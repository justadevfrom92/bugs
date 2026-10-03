<?php

namespace App\Console\Commands;

class ProcessAutopay extends TrackedCommand
{
    protected $signature = 'et:autopay {--user= : ID of the admin who ran it}';

    protected $description = 'Charge AutoPay drafts';

    protected function work(): array
    {
        return $this->needsIntegration('stripe', 'Charge AutoPay drafts');
    }
}
