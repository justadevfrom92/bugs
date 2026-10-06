<?php

namespace App\Reports;

use App\Models\WorkItem;
use Illuminate\Support\Collection;

class ExceptionsReport extends Report
{
    public function filters(): array
    {
        return ['queue' => ['Queue', 'select', collect(config('admin.queues'))->map(fn ($q) => $q[0])->all()], 'open' => ['Show', 'select', ['open' => 'Open only', 'all' => 'Open and fixed']]];
    }

    public function defaults(): array
    {
        return ['queue' => null, 'open' => 'open'];
    }

    public function query(array $f): Collection
    {
        return WorkItem::with('customer')->when(($f['open'] ?? 'open') === 'open', fn ($q) => $q->whereNull('resolved_at'))
            ->when($f['queue'] ?? null, fn ($q, $k) => $q->where('queue', $k))->orderBy('created_at')->get();
    }

    public function columns(): array
    {
        return ['Queue', 'Account', 'Customer', 'Summary', 'Opened', 'Days Open', 'Fixed'];
    }

    public function row($w): array
    {
        return [config('admin.queues.'.$w->queue)[0] ?? $w->queue, $w->customer?->account, $w->customer?->name, $w->summary, $w->created_at->toDateString(),
            (int) $w->created_at->diffInDays($w->resolved_at ?? now()), $w->resolved_at?->toDateString()];
    }
}
