<?php

namespace App\Console\Commands;

class ErcotResponses extends TrackedCommand
{
    protected $signature = 'et:ercot-responses {--user= : ID of the admin who ran it}';

    protected $description = 'Pull ERCOT 814 enrollment responses';

    protected function work(): array
    {
        return $this->needsIntegration('utilibill', 'Pull ERCOT 814 enrollment responses');
    }
}
