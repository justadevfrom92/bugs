<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** An IP address or an area of the website that visitors are kept out of (Lando → Site). The admin is never blocked. */
class SiteBlock extends Model
{
    use RecordsHistory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'expires_at' => 'datetime', 'last_hit_at' => 'datetime'];
    }

    public function scopeInForce(Builder $q): void
    {
        $q->where('active', true)->where(fn ($w) => $w->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function isInForce(): bool
    {
        return $this->active && (! $this->expires_at || $this->expires_at->isFuture());
    }

    /** 203.0.113.7 matches itself; 203.0.113.* matches the whole range. */
    public function matchesIp(string $ip): bool
    {
        return str_ends_with($this->value, '*') ? str_starts_with($ip, rtrim($this->value, '*')) : $ip === $this->value;
    }

    /** "checkout" covers checkout and everything under it, like checkout/deposit. */
    public function matchesPath(string $path): bool
    {
        return $path === $this->value || str_starts_with($path, rtrim($this->value, '/').'/');
    }

    public function historyLabel(): string
    {
        return ($this->type === 'ip' ? 'IP ' : 'Area ').$this->value;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
