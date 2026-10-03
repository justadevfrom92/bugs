<?php

namespace App\Console\Commands;

use App\Models\Market;
use App\Models\Plan;
use App\Services\Catalog;
use Illuminate\Support\Facades\Storage;

/** Writes today's EFL average prices for every active plan and market to storage/app/exports. */
class ExportRates extends TrackedCommand
{
    protected $signature = 'et:export-rates {--user= : ID of the admin who ran it}';

    protected $description = 'Export current rates to a CSV file';

    protected function work(): array
    {
        $catalog = app(Catalog::class);
        $rates = $catalog->currentRates();
        $fees = $catalog->currentFees();
        $lines = [['plan_code', 'plan_name', 'term', 'market', 'energy_cents', 'avg_500', 'avg_1000', 'avg_2000', 'etf', 'renewable_pct']];

        foreach (Plan::where('active', true)->orderBy('internal')->get() as $p) {
            foreach (Market::orderBy('name')->get() as $m) {
                $r = $rates[$p->id.'-'.$m->id] ?? null;
                $f = $fees[$m->id] ?? null;
                if (! $r || ! $f) {
                    continue;
                }
                $avg = fn ($kwh) => number_format(Catalog::averagePrice($r->energy, $f->per_kwh, $f->per_bill, $p->mrc, $kwh), 1, '.', '');
                $lines[] = [$p->internal, $p->name, $p->term, $m->name, $r->energy, $avg(500), $avg(1000), $avg(2000), $p->etf, $p->green];
            }
        }

        $csv = collect($lines)->map(fn ($row) => collect($row)->map(fn ($v) => '"'.str_replace('"', '""', (string) $v).'"')->implode(','))->implode("\n")."\n";
        $path = 'exports/rates-'.today()->toDateString().'.csv';
        Storage::disk('local')->put($path, $csv);

        return ['OK', (count($lines) - 1).' rates written to storage/app/private/'.$path];
    }
}
