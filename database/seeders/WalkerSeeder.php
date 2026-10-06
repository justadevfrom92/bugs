<?php

namespace Database\Seeders;

use App\Jobs\RunReport;
use App\Models\ReportRun;
use App\Models\ReportUpload;
use App\Models\User;
use App\Reports\Report;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/** Sample Walker data: report runs in every state and a few uploaded report files. */
class WalkerSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $finance = User::where('email', 'finance@example.com')->first() ?? $admin;
        $make = function (string $key, ?User $by, array $params, string $status, $at) {
            $def = Report::definition($key);

            return ReportRun::create(['user_id' => $by?->id, 'app' => 'walker', 'report' => $key, 'title' => $def['title'], 'model' => $def['model'],
                'params' => $params + Report::make($key)->defaults(), 'rows' => 0, 'status' => $status, 'created_at' => $at]);
        };

        // Completed: actually run, so they have real results and files
        foreach ([['payments', $finance, 26], ['receivables', $finance, 8], ['orders', $admin, 5], ['bills', $finance, 2], ['stars', $admin, 1]] as [$key, $by, $hoursAgo]) {
            $run = $make($key, $by, [], 'queued', now()->subHours($hoursAgo));
            (new RunReport($run->id))->handle();
            $run->update(['started_at' => now()->subHours($hoursAgo), 'finished_at' => now()->subHours($hoursAgo)->addSeconds(4), 'duration_ms' => 4000]);
        }
        // Error
        $make('ercot', $admin, ['type' => '867_03'], 'failed', now()->subHours(3))
            ->update(['started_at' => now()->subHours(3), 'finished_at' => now()->subHours(3)->addSeconds(41),
                'error' => 'Utilibill export timed out after 40 seconds (sample error).']);
        // Running now, and one that never finished
        $make('contacts', $admin, [], 'running', now()->subMinutes(4))->update(['started_at' => now()->subMinutes(4)]);
        $make('exceptions', $finance, ['open' => 'all'], 'running', now()->subHours(5))->update(['started_at' => now()->subHours(5)]);

        // Uploaded reports (small sample files)
        foreach ([
            ['Monthly Revenue Close - September', 'Finance', 'revenue-close-2026-09.csv', "Account Group,Revenue\nResidential,412880.11\nSmall Business,98012.40\n"],
            ['ERCOT 867 Usage Exceptions', 'Utility / ERCOT', 'ercot-867-exceptions.csv', "ESIID,Issue\n10089011000000000000,Missing read\n"],
            ['Q3 Campaign Results', 'Marketing', 'q3-campaigns.csv', "Campaign,Sent,Opened\nFall Renewal,1200,540\n"],
        ] as [$title, $category, $name, $content]) {
            $path = 'report-uploads/sample-'.$name;
            Storage::disk('local')->put($path, $content);
            ReportUpload::create(['title' => $title, 'category' => $category, 'path' => $path, 'original_name' => $name, 'size' => strlen($content),
                'user_id' => ($category === 'Finance' ? $finance : $admin)?->id, 'description' => 'Sample file']);
        }
    }
}
