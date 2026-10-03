<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Template extends Model
{
    protected $guarded = ['id'];

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }
}
