<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Utility delivery charges. Each change adds a row; the current one is the latest effective_on <= today. */
class TdspFee extends Model
{
    use RecordsHistory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['per_kwh' => 'float', 'per_bill' => 'float', 'effective_on' => 'date'];
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }
}
