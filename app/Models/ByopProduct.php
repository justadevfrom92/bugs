<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Build Your Own Plan add-on. `key` matches the option's data-product on the website. */
class ByopProduct extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['rate_adj' => 'float', 'monthly' => 'float', 'active' => 'boolean'];
    }
}
