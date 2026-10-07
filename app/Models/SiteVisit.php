<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One page view on the website (Lando → Site → Visitors). */
class SiteVisit extends Model
{
    public const UPDATED_AT = null;

    /** A page still sending its heartbeat this recently is "on the site now". */
    public const ONLINE_MINUTES = 5;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime', 'seen_at' => 'datetime', 'blocked' => 'boolean'];
    }

    public function scopeOnline(Builder $q): void
    {
        $q->where('seen_at', '>=', now()->subMinutes(self::ONLINE_MINUTES));
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
