<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\HistoryItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/** History: per customer (Corral), per admin user (Sheriff), and a detail page for each entry. */
class HistoryController extends Controller
{
    /** Data for a customer's History tab. */
    public static function forCustomer(Request $request, Customer $customer): array
    {
        $base = HistoryItem::where('customer_id', $customer->id);
        $filters = $request->only(['hmodel', 'hgroup']);

        $items = self::filtered(clone $base, $filters)->with('user')->latest('created_at')->latest('id')
            ->limit($request->boolean('hall') ? 5000 : 200)->get();

        // Running star total, oldest first, like the original Rewards History
        $net = 0;
        $rewards = (clone $base)->where('model', 'ItemProductReward_model')->oldest('created_at')->get()
            ->filter(fn ($i) => isset($i->data['stars']))
            ->map(function ($i) use (&$net) {
                $net += (float) $i->data['stars'];

                return ['date' => $i->created_at, 'reason' => $i->data['reason'] ?? $i->data['product'] ?? 'Stars', 'stars' => (float) $i->data['stars'], 'net' => $net];
            })->reverse()->values();

        // kWh by year and month from the bills
        $usage = $customer->bills->groupBy(fn ($b) => $b->billed_on->copy()->subMonth()->year)
            ->map(fn ($bills) => $bills->mapWithKeys(fn ($b) => [$b->billed_on->copy()->subMonth()->month => $b->kwh]))
            ->sortKeysDesc();

        return [
            'historyItems' => $items,
            'historyTotal' => (clone $base)->count(),
            'historyShown' => self::filtered(clone $base, $filters)->count(),
            'historyCounts' => self::counts(clone $base),
            'historyFilters' => $filters,
            'rewardsHistory' => $rewards,
            'usageHistory' => $usage,
        ];
    }

    /** Sheriff → Users → History: everything one admin user did. */
    public function user(Request $request, User $user): View
    {
        $base = HistoryItem::where('user_id', $user->id);
        $filters = $request->only(['hmodel', 'hgroup']);

        return view('admin.history.user', [
            'u' => $user,
            'items' => self::filtered(clone $base, $filters)->with('customer')->latest('created_at')->latest('id')->paginate(100)->withQueryString(),
            'counts' => self::counts(clone $base),
            'filters' => $filters,
            'total' => (clone $base)->count(),
        ]);
    }

    /** One entry: its fields, changes, parent account and related entries. */
    public function show(HistoryItem $item): View
    {
        $item->load(['customer', 'user']);
        $related = HistoryItem::where('model', $item->model)->whereKeyNot($item->id)
            ->where(fn ($q) => $item->customer_id
                ? $q->where('customer_id', $item->customer_id)
                : $q->where('record_id', $item->record_id))
            ->latest('created_at')->limit(50)->get();

        return view('admin.history.show', ['item' => $item, 'related' => $related]);
    }

    private static function filtered(Builder $q, array $f): Builder
    {
        return $q->when($f['hmodel'] ?? null, fn ($q, $m) => $q->where('model', $m))
            ->when($f['hgroup'] ?? null, fn ($q, $g) => $q->where('group', $g));
    }

    /** Counts per model, grouped and ordered like config('history.groups'). */
    private static function counts(Builder $q): Collection
    {
        $order = array_flip(config('history.groups'));

        return $q->select(['group', 'model'])->selectRaw('count(*) as n, max(created_at) as last_at')
            ->groupBy('group', 'model')->get()
            ->groupBy('group')
            ->sortBy(fn ($rows, $group) => $order[$group] ?? 99)
            ->map(fn ($rows) => $rows->sortBy('model')->values());
    }
}
