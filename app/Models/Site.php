<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A website served by this install. Each has its own pages; content blocks can be shared. */
class Site extends Model
{
    use RecordsHistory;

    protected $guarded = ['id'];

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(ContentBlock::class);
    }

    /** The site for a request's host name, else the first active site. */
    public static function forHost(?string $host): ?self
    {
        return self::where('status', 'Active')->where('domain', $host)->first()
            ?? self::where('status', 'Active')->orderBy('id')->first();
    }
}
