<?php

namespace App\Services;

use App\Models\ContactLog;
use App\Models\Customer;
use App\Models\CustomerProduct;
use App\Models\LedgerEntry;
use App\Models\RewardOffer;
use App\Models\StarEntry;
use App\Models\User;
use App\Support\History;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Rewards stars: the offers and redeeming them. Used by Corral, My Account and the rewards admin app. */
class Rewards
{
    /** The active offers (Bounty → Offers), cheapest first. */
    public static function offers(): Collection
    {
        return RewardOffer::where('active', true)->orderBy('position')->orderBy('stars')->get();
    }

    /** Redeem an offer. Throws RuntimeException with a message the customer can read when it can't. */
    public static function redeem(Customer $customer, int $offerId, ?User $by = null): string
    {
        $offer = RewardOffer::where('active', true)->find($offerId);
        if (! $offer) {
            throw new RuntimeException('That offer is no longer available.');
        }
        [$cost, $name] = [$offer->stars, $offer->name];
        $effect = $offer->effect.($offer->value !== null ? ':'.$offer->value : '');
        if ($cost > $customer->stars) {
            throw new RuntimeException('Not enough stars: '.$name.' needs '.$cost.', the account has '.$customer->stars.'.');
        }

        DB::transaction(function () use ($customer, $by, $cost, $name, $effect, $offer) {
            if ($cost) {
                StarEntry::create(['customer_id' => $customer->id, 'reason' => 'Redeemed: '.$name, 'stars' => -$cost, 'user_id' => $by?->id,
                    'reward_offer_id' => $offer->id, 'fulfillment' => $offer->effect === 'gift' ? 'pending' : null]);
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
