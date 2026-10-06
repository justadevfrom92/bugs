<?php

namespace App\Reports;

use App\Models\ContactLog;
use Illuminate\Support\Collection;

class ContactsReport extends Report
{
    public function filters(): array
    {
        return ['start' => ['Start', 'date'], 'end' => ['End', 'date'], 'channel' => ['Channel', 'select', ['Email' => 'Email', 'SMS' => 'SMS']]];
    }

    public function query(array $f): Collection
    {
        return ContactLog::with('customer')->whereDate('created_at', '>=', $f['start'])->whereDate('created_at', '<=', $f['end'])
            ->when($f['channel'] ?? null, fn ($q, $c) => $q->where('channel', $c))->orderBy('created_at')->get();
    }

    public function columns(): array
    {
        return ['Date', 'Account', 'Channel', 'Template', 'Status', 'Sent', 'Opened', 'Clicked'];
    }

    public function row($l): array
    {
        return [$l->created_at->format('Y-m-d H:i'), $l->customer?->account, $l->channel, $l->template, $l->status, $l->sent_at?->format('Y-m-d H:i'), $l->opened_at?->format('Y-m-d H:i'), $l->clicked_at?->format('Y-m-d H:i')];
    }

    public function summary(Collection $logs): array
    {
        return [['Template', 'Sent', 'Opened', 'Clicked'], $logs->groupBy('template')
            ->map(fn ($g, $t) => [$t, $g->count(), $g->whereNotNull('opened_at')->count(), $g->whereNotNull('clicked_at')->count()])->values()];
    }
}
