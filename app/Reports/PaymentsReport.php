<?php

namespace App\Reports;

use App\Models\Payment;
use Illuminate\Support\Collection;

class PaymentsReport extends Report
{
    public function filters(): array
    {
        $statuses = ['Success' => 'Success', 'Pending' => 'Pending', 'Failed' => 'Failed', 'Reversed' => 'Reversed'];

        return ['start' => ['Start', 'date'], 'end' => ['End', 'date'], 'status' => ['Status', 'select', $statuses], 'source' => ['Source', 'text']];
    }

    public function query(array $f): Collection
    {
        return Payment::with('customer')->whereDate('paid_on', '>=', $f['start'])->whereDate('paid_on', '<=', $f['end'])
            ->when($f['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($f['source'] ?? null, fn ($q, $s) => $q->where('source', $s))->orderBy('paid_on')->get();
    }

    public function columns(): array
    {
        return ['Paid On', 'Account', 'Customer', 'Amount', 'Method', 'Source', 'Kind', 'Status', 'Reference'];
    }

    public function row($p): array
    {
        return [$p->paid_on?->toDateString(), $p->customer?->account, $p->customer?->name, (float) $p->amount, $p->method, $p->source, $p->kind, $p->status, $p->reference];
    }

    public function summary(Collection $payments): array
    {
        return [['Status', 'Method', 'Payments', 'Amount'], $payments->groupBy(fn ($p) => $p->status.'|'.$p->method)
            ->map(fn ($g, $k) => [...explode('|', $k), $g->count(), round($g->sum('amount'), 2)])->values()];
    }
}
