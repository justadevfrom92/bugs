<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A website sign-up saved to finish later. Card and SSN details are never saved here. */
class Signup extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }
}
