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

    /** The page of the record this entry is about (the email, text, call or other item), if it still exists. */
    public function recordUrl(): ?string
    {
        if (! $this->record_id || $this->action === 'deleted') {
            return null;
        }

        return match (true) {
            $this->model === 'ItemSms_model' => ContactLog::where('channel', 'SMS')->whereKey($this->record_id)->exists() ? route('corral.sms.show', $this->record_id) : null,
            $this->model === 'ItemCall_model' => Phonecall::whereKey($this->record_id)->exists() ? route('corral.calls.transcript', $this->record_id) : null,
            config()->has('items.models.'.$this->model) => route('corral.items.show', [$this->record_id, $this->model]),
            default => null,
        };
    }

    /** "Admin Demo", or "System" for automatic and customer-driven events. */
    public function actor(): string
    {
        return $this->user?->name ?? 'System';
    }
}
