<?php

namespace App\Reports;

use App\Models\Bill;
use Illuminate\Support\Collection;

class BillsReport extends Report
{
    public function filters(): array
    {
        return ['start' => ['Start', 'date'], 'end' => ['End', 'date']];
    }

    public function query(array $f): Collection
    {
        return Bill::with('customer.market')->whereDate('billed_on', '>=', $f['start'])->whereDate('billed_on', '<=', $f['end'])->orderBy('billed_on')->get();
    }

    public function columns(): array
    {
        return ['Billed On', 'Account', 'Customer', 'Market', 'kWh', 'Amount', 'Due', 'Paid On', 'Invoice'];
    }

    public function row($b): array
    {
        return [$b->billed_on->toDateString(), $b->customer?->account, $b->customer?->name, $b->customer?->market?->name, (int) $b->kwh, (float) $b->amount,
            $b->due_on?->toDateString(), $b->paid_on?->toDateString(), $b->invoice];
    }

    public function summary(Collection $bills): array
    {
        return [['Market', 'Bills', 'kWh', 'Billed'], $bills->groupBy(fn ($b) => $b->customer?->market?->name ?? '—')
            ->map(fn ($g, $m) => [$m, $g->count(), (int) $g->sum('kwh'), round($g->sum('amount'), 2)])->values()];
    }
}
