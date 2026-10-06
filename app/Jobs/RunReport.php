<?php

namespace App\Jobs;

use App\Models\ReportRun;
use App\Reports\Report;
use App\Support\Xlsx;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Runs a report in the background (the queue worker: php artisan queue:work),
 * saves it as an .xlsx and keeps the first rows for the run's page in Walker.
 */
class RunReport implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;

    public int $tries = 1;

    public function __construct(public int $runId) {}

    public function handle(): void
    {
        $run = ReportRun::findOrFail($this->runId);
        $run->update(['status' => 'running', 'started_at' => now(), 'error' => null]);
        $started = microtime(true);

        $report = Report::make($run->report);
        $records = $report->query($run->params + $report->defaults());
        [$header, $rows] = $report->detail($records);
        $file = 'reports/'.$run->report.'-'.$run->id.'.xlsx';
        Storage::disk('local')->put($file, Xlsx::build($header, $rows, $run->title ?? $run->report));

        $run->update(['status' => 'done', 'file' => $file, 'rows' => $records->count(), 'finished_at' => now(),
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            'preview' => ['header' => $header, 'rows' => $rows->take(50)->values()->all()]]);
    }

    public function failed(Throwable $e): void
    {
        ReportRun::whereKey($this->runId)->update(['status' => 'failed', 'finished_at' => now(), 'error' => mb_substr($e->getMessage(), 0, 2000)]);
    }
}
