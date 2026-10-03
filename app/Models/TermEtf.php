<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Astro: early termination fee by term length. */
class TermEtf extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'term';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'term' => 'integer'];
    }
}
