<?php

namespace Database\Seeders;

use App\Models\JobRun;
use App\Models\ReferenceRow;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/** Sheriff: reference data tables and recent job history. */
class SystemSeeder extends Seeder
{
    public function run(): void
    {
        $tables = DatabaseSeeder::data('data_tables');
        foreach (config('admin.reference_tables') as $key => [$name]) {
            foreach ($tables[$name]['rows'] ?? [] as $i => $cells) {
                ReferenceRow::create(['table_key' => $key, 'position' => $i, 'cells' => array_map('strval', $cells)]);
            }
        }

        $byName = collect(config('admin.jobs'))->mapWithKeys(fn ($job, $command) => [$job[0] => $command]);
        foreach (DatabaseSeeder::data('crons') as $c) {
            $command = $byName[$c['name']] ?? null;
            if (! $command) {
                continue;
            }
            $start = Carbon::parse($c['last']);
            JobRun::create([
                'command' => $command, 'status' => $c['status'],
                'message' => $c['status'] === 'Failed' ? 'Sample failure from seed data' : null,
                'started_at' => $start, 'finished_at' => $start->copy()->addMilliseconds($c['ms']), 'duration_ms' => $c['ms'],
            ]);
        }
    }
}
