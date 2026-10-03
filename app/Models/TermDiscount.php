<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Astro: ¢/kWh discount by term and region. Positive lowers the rate. */
class TermDiscount extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['discount' => 'float', 'term' => 'integer'];
    }
}
