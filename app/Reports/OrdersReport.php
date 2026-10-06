<?php

namespace App\Reports;

use App\Models\Customer;
use Illuminate\Support\Collection;

class OrdersReport extends Report
{
    public function filters(): array
    {
        $statuses = array_keys(config('admin.customer_statuses'));

        return ['start' => ['Start', 'date'], 'end' => ['End', 'date'], 'statuses' => ['Statuses', 'multi', array_combine($statuses, $statuses)]];
    }

    public function defaults(): array
    {
        return parent::defaults() + ['statuses' => array_keys(config('admin.customer_statuses'))];
    }

    public function query(array $f): Collection
    {
        return Customer::with(['plan', 'market'])
            ->whereDate('created_at', '>=', $f['start'])->whereDate('created_at', '<=', $f['end'])
            ->whereIn('status', $f['statuses'])->orderBy('created_at')->get();
    }

    public function columns(): array
    {
        return ['Account', 'Created', 'Status', 'Exception', 'Customer', 'Type', 'Phone', 'Email', 'Address', 'City', 'Zip', 'Market', 'ESIID', 'Plan', 'Source', 'MSID'];
    }

    public function row($c): array
    {
        return [$c->account, $c->created_at->toDateString(), $c->status, $c->exception, $c->name, $c->type, $c->phone, $c->email,
            $c->address, $c->city, $c->zip, $c->market?->name, $c->esiid, $c->plan?->internal, $c->source, $c->msid];
    }

    public function summary(Collection $orders): array
    {
        return [['Status', 'Orders'], $orders->countBy('status')->map(fn ($n, $s) => [$s, $n])->values()];
    }
}
