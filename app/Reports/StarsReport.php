<?php

namespace App\Reports;

use App\Models\StarEntry;
use Illuminate\Support\Collection;

class StarsReport extends Report
{
    public function filters(): array
    {
        return ['start' => ['Start', 'date'], 'end' => ['End', 'date']];
    }

    public function query(array $f): Collection
    {
        return StarEntry::with('customer')->whereDate('created_at', '>=', $f['start'])->whereDate('created_at', '<=', $f['end'])->orderBy('created_at')->get();
    }

    public function columns(): array
    {
        return ['Date', 'Account', 'Customer', 'Reason', 'Stars'];
    }

    public function row($s): array
    {
        return [$s->created_at->toDateString(), $s->customer?->account, $s->customer?->name, $s->reason, (float) $s->stars];
    }

    public function summary(Collection $entries): array
    {
        return [['Reason', 'Entries', 'Stars'], $entries->groupBy(fn ($s) => str_starts_with($s->reason, 'Redeemed') ? 'Redeemed' : $s->reason)
            ->map(fn ($g, $r) => [$r, $g->count(), $g->sum('stars')])->values()];
    }
}
