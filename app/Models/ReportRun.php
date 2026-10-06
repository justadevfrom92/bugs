<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One run of a report: from Corral's report screens or Walker. Shown on Walker's home page by status. */
class ReportRun extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['params' => 'array', 'preview' => 'array', 'created_at' => 'datetime', 'started_at' => 'datetime', 'finished_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** queued | running | done | failed | stalled (still running past walker.timeout_minutes: "Did not finish") */
    public function state(): string
    {
        if (in_array($this->status, ['queued', 'running'], true) && ($this->started_at ?? $this->created_at)->lt(now()->subMinutes(config('walker.timeout_minutes')))) {
            return 'stalled';
        }

        return $this->status;
    }

    public static function stateLabel(string $state): array
    {
        return ['queued' => ['Queued', 'info'], 'running' => ['Running', 'info'], 'done' => ['Completed', 'ok'], 'failed' => ['Error', 'bad'], 'stalled' => ['Did not finish', 'warn']][$state] ?? [$state, ''];
    }

    public function scopeInState(Builder $q, string $state): Builder
    {
        $cut = now()->subMinutes(config('walker.timeout_minutes'));

        return match ($state) {
            'running' => $q->whereIn('status', ['queued', 'running'])->where(fn ($w) => $w->where('started_at', '>=', $cut)->orWhere(fn ($x) => $x->whereNull('started_at')->where('created_at', '>=', $cut))),
            'done' => $q->where('status', 'done'),
            'problem' => $q->where(fn ($w) => $w->where('status', 'failed')->orWhere(fn ($x) => $x->whereIn('status', ['queued', 'running'])
                ->where(fn ($y) => $y->where('started_at', '<', $cut)->orWhere(fn ($z) => $z->whereNull('started_at')->where('created_at', '<', $cut))))),
            default => $q,
        };
    }
}
