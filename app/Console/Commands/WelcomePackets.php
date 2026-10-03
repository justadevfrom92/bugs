<?php

namespace App\Console\Commands;

class WelcomePackets extends TrackedCommand
{
    protected $signature = 'et:welcome-packets {--user= : ID of the admin who ran it}';

    protected $description = 'Email welcome packets to new customers';

    protected function work(): array
    {
        return $this->needsIntegration('amazon', 'Email welcome packets to new customers');
    }
}
