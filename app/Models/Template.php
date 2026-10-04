<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Template extends Model
{
    use RecordsHistory;

    protected $guarded = ['id'];

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }
}
