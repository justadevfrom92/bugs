<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One recorded event. See config/history.php and App\Models\Concerns\RecordsHistory. */
class HistoryItem extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['data' => 'array', 'changes' => 'array', 'created_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** "Admin Demo", or "System" for automatic and customer-driven events. */
    public function actor(): string
    {
        return $this->user?->name ?? 'System';
    }
}
