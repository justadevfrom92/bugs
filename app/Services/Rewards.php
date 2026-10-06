<?php

namespace App\Services;

use App\Models\ContactLog;
use App\Models\Customer;
use App\Models\CustomerProduct;
use App\Models\LedgerEntry;
use App\Models\StarEntry;
use App\Models\User;
use App\Support\History;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Rewards stars: the offers and redeeming them. Used by Corral, My Account and the rewards admin app. */
class Rewards
{
    /** @return array<int, array{0: int, 1: string, 2: string, 3: string}> [stars, offer, description, effect] */
    public static function offers(): array
    {
        return config('corral.reward_offers');
    }

    /** Redeem an offer. Throws RuntimeException with a message the customer can read when it can't. */
    public static function redeem(Customer $customer, int $offer, ?User $by = null): string
    {
        $offers = self::offers();
        if (! isset($offers[$offer])) {
            throw new RuntimeException('That offer is no longer available.');
        }
        [$cost, $name, , $effect] = $offers[$offer];
        if ($cost > $customer->stars) {
            throw new RuntimeException('Not enough stars: '.$name.' needs '.$cost.', the account has '.$customer->stars.'.');
        }

        DB::transaction(function () use ($customer, $by, $cost, $name, $effect) {
            if ($cost) {
                StarEntry::create(['customer_id' => $customer->id, 'reason' => 'Redeemed: '.$name, 'stars' => -$cost, 'user_id' => $by?->id]);
            }
            if (str_starts_with($effect, 'credit:')) {
                LedgerEntry::create(['customer_id' => $customer->id, 'kind' => 'credit', 'amount' => (float) substr($effect, 7), 'description' => $name, 'user_id' => $by?->id]);
            } elseif ($effect === 'gift') {
                ContactLog::create(['customer_id' => $customer->id, 'channel' => 'Email', 'template' => 'E-Gift Card: '.$name, 'status' => 'queued', 'user_id' => $by?->id]);
            } elseif ($effect === 'drawing') {
                History::record(['customer_id' => $customer->id, 'model' => 'ItemDrawingEntry_model', 'group' => 'Products', 'action' => 'created', 'summary' => 'Monthly drawing entry', 'data' => ['month' => now()->format('Y-m')]]);
            } elseif (str_starts_with($effect, 'product:')) {
                CustomerProduct::firstOrCreate(['customer_id' => $customer->id, 'product' => substr($effect, 8), 'removed_at' => null], ['user_id' => $by?->id]);
            }
            $customer->syncStars();
            $customer->notes()->create(['user_id' => $by?->id, 'author' => $by?->name ?? 'Customer (MyAccount)', 'category' => 'Rewards', 'action' => 'Redeem Stars', 'priority' => 'Low',
                'body' => 'Redeemed '.$cost.' stars: '.$name.'.']);
        });

        return 'Redeemed: '.$name;
    }
}
