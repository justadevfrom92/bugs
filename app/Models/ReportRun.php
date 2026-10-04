<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One run of a Corral report, listed under "Recent Results". */
class ReportRun extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['params' => 'array', 'created_at' => 'datetime'];
    }
}
