<?php

namespace App\Console\Commands;

use App\Models\JobRun;
use Illuminate\Console\Command;
use Throwable;

/**
 * Base for the scheduled jobs listed in config('admin.jobs'). Every run is
 * recorded in job_runs so Sheriff → Crons can show its status.
 */
abstract class TrackedCommand extends Command
{
    /** Do the work; return [status, message]. Status is OK, Warning, Skipped or Failed. */
    abstract protected function work(): array;

    public function handle(): int
    {
        $run = JobRun::create([
            'command' => $this->getName(), 'status' => 'Running', 'started_at' => now(),
            'triggered_by' => $this->option('user') ?: null,
        ]);
        $start = hrtime(true);

        try {
            [$status, $message] = $this->work();
        } catch (Throwable $e) {
            report($e);
            [$status, $message] = ['Failed', $e->getMessage()];
        }

        $run->update([
            'status' => $status, 'message' => $message, 'finished_at' => now(),
            'duration_ms' => (int) ((hrtime(true) - $start) / 1e6),
        ]);
        $this->line($status.($message ? ': '.$message : ''));

        return $status === 'Failed' ? self::FAILURE : self::SUCCESS;
    }

    /** Null when the integration's .env values are all set, otherwise a reason to skip. */
    protected function missingIntegration(string $key): ?string
    {
        $i = config('admin.integrations.'.$key);
        $missing = collect($i['env'])->filter(fn ($v) => blank($v))->keys();

        return $missing->isEmpty() ? null : $i['name'].' is not configured (set '.$missing->implode(', ').' in .env)';
    }

    /**
     * Jobs that talk to an outside system. Without credentials they skip; with credentials
     * they report that the API client still has to be written for that provider.
     */
    protected function needsIntegration(string $key, string $what): array
    {
        if ($reason = $this->missingIntegration($key)) {
            return ['Skipped', $reason];
        }

        return ['Failed', config('admin.integrations.'.$key.'.name').' credentials are set, but the API client for "'.$what.'" has not been built yet'];
    }
}
