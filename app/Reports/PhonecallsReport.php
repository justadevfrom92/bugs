<?php

namespace App\Reports;

use App\Models\Phonecall;
use Illuminate\Support\Collection;

class PhonecallsReport extends Report
{
    public function filters(): array
    {
        return ['start' => ['Start', 'date'], 'end' => ['End', 'date'], 'account' => ['Account Number', 'text'], 'agent' => ['Agent Id', 'text'], 'phone' => ['Phone Number', 'text']];
    }

    public function defaults(): array
    {
        return parent::defaults() + ['account' => null, 'agent' => null, 'phone' => null];
    }

    public function query(array $f): Collection
    {
        $digits = preg_replace('/\D/', '', (string) ($f['phone'] ?? ''));

        return Phonecall::with(['customer', 'user'])
            ->whereDate('started_at', '>=', $f['start'])->whereDate('started_at', '<=', $f['end'])
            ->when($f['account'] ?? null, fn ($q, $a) => $q->whereHas('customer', fn ($c) => $c->where('account', trim($a))))
            ->when($f['agent'] ?? null, fn ($q, $a) => $q->where('agent_id', trim($a)))
            ->latest('started_at')->get()
            // Phone numbers are stored formatted; compare digits only
            ->when($digits, fn ($c) => $c->filter(fn ($call) => str_contains(preg_replace('/\D/', '', $call->phone), $digits))->values());
    }

    public function columns(): array
    {
        return ['Started', 'Direction', 'Phone', 'Account', 'Customer', 'Agent Id', 'Agent', 'Seconds', 'Disposition'];
    }

    public function row($c): array
    {
        return [$c->started_at->format('Y-m-d H:i'), $c->direction, $c->phone, $c->customer?->account, $c->customer?->name, $c->agent_id, $c->user?->name, $c->duration_sec, $c->disposition];
    }

    public function summary(Collection $calls): array
    {
        return [['Agent Id', 'Calls', 'Inbound', 'Outbound', 'Minutes'], $calls->groupBy(fn ($c) => $c->agent_id ?? '—')
            ->map(fn ($g, $agent) => [$agent, $g->count(), $g->where('direction', 'inbound')->count(), $g->where('direction', 'outbound')->count(), (int) round($g->sum('duration_sec') / 60)])
            ->sortKeys()->values()];
    }
}
