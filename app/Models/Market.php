<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A utility service territory (TDSP), e.g. TX-E-ONCOR. */
class Market extends Model
{
    use RecordsHistory;

    protected $guarded = ['id'];

    public function fees(): HasMany
    {
        return $this->hasMany(TdspFee::class);
    }

    public function zipRanges(): HasMany
    {
        return $this->hasMany(ZipRange::class);
    }
}
