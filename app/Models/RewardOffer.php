<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;

/** Something customers can spend stars on (Bounty → Offers). */
class RewardOffer extends Model
{
    use RecordsHistory;

    public const EFFECTS = ['credit' => 'Bill credit ($)', 'gift' => 'E-gift card (emailed)', 'drawing' => 'Monthly drawing entry', 'product' => 'Product added to the account'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'stars' => 'integer'];
    }
}
