<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;

/** How customers earn stars (Bounty → Earning Rules), used by the et:rewards-stars job. */
class RewardRule extends Model
{
    use RecordsHistory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'per_dollar' => 'boolean', 'stars' => 'float'];
    }

    public static function stars(string $key): ?RewardRule
    {
        return self::where('key', $key)->where('active', true)->first();
    }
}
