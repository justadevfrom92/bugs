<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZipRange extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }
}
