<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Energy charge (¢/kWh) for a plan in a market. Each change adds a row with its effective date. */
class Rate extends Model
{
    use RecordsHistory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['energy' => 'float', 'effective_on' => 'date'];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }
}
