<?php

namespace App\Reports;

use App\Models\ErcotTransaction;
use Illuminate\Support\Collection;

class ErcotReport extends Report
{
    public function filters(): array
    {
        return ['start' => ['Start', 'date'], 'end' => ['End', 'date'], 'type' => ['Trans Type', 'text'], 'status' => ['Status', 'select', ['linked' => 'linked', 'unlinked' => 'unlinked', 'cancelled' => 'cancelled']]];
    }

    public function query(array $f): Collection
    {
        return ErcotTransaction::with('customer')->whereDate('trans_date', '>=', $f['start'])->whereDate('trans_date', '<=', $f['end'])
            ->when($f['type'] ?? null, fn ($q, $t) => $q->where('trans_type', $t))
            ->when($f['status'] ?? null, fn ($q, $s) => $q->where('status', $s))->orderBy('trans_date')->get();
    }

    public function columns(): array
    {
        return ['Trans Date', 'Account', 'ESIID', 'Purpose', 'Trans Type', 'Label', 'Tracking', 'Status'];
    }

    public function row($t): array
    {
        return [$t->trans_date->toDateString(), $t->customer?->account, $t->esiid, $t->purpose, $t->trans_type, $t->label, $t->tracking, $t->status];
    }
}
