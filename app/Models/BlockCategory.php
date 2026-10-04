<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlockCategory extends Model
{
    protected $guarded = ['id'];

    public function blocks(): HasMany
    {
        return $this->hasMany(ContentBlock::class, 'category_id');
    }
}
