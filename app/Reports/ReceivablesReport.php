<?php

namespace App\Reports;

use App\Models\Customer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/** Accounts receivable: open balances, aged by how far past the due date they are. */
class ReceivablesReport extends Report
{
    public function filters(): array
    {
        return ['as_of' => ['As Of', 'date'], 'min' => ['Minimum Balance ($)', 'text']];
    }

    public function defaults(): array
    {
        return ['as_of' => today()->toDateString(), 'min' => '0.01'];
    }

    public function rules(): array
    {
        return ['as_of' => ['nullable', 'date'], 'min' => ['nullable', 'numeric', 'min:0']];
    }

    public function query(array $f): Collection
    {
        return Customer::with('market')->where('balance', '>=', (float) ($f['min'] ?? 0.01))->orderByDesc('balance')->get()
            ->each(fn ($c) => $c->setAttribute('days_past_due', $c->due_date ? max(0, (int) $c->due_date->diffInDays(Carbon::parse($f['as_of']), false)) : 0));
    }

    public static function bucket(int $days): string
    {
        return match (true) {
            $days === 0 => 'Current', $days <= 30 => '1-30', $days <= 60 => '31-60', $days <= 90 => '61-90', default => '90+'
        };
    }

    public function columns(): array
    {
        return ['Account', 'Customer', 'Status', 'Market', 'Balance', 'Due Date', 'Days Past Due', 'Aging'];
    }

    public function row($c): array
    {
        return [$c->account, $c->name, $c->status, $c->market?->name, (float) $c->balance, $c->due_date?->toDateString(), $c->days_past_due, self::bucket($c->days_past_due)];
    }

    public function summary(Collection $customers): array
    {
        return [['Aging', 'Accounts', 'Balance'], collect(['Current', '1-30', '31-60', '61-90', '90+'])
            ->map(fn ($b) => [$b, ($g = $customers->filter(fn ($c) => self::bucket($c->days_past_due) === $b))->count(), round($g->sum('balance'), 2)])];
    }
}
