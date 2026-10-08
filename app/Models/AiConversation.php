<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One conversation an AI agent had (a call, text thread, chat, email or test), with its transcript. */
class AiConversation extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime'];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class, 'ai_agent_id');
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(AiModel::class, 'ai_model_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function handedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handed_to_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** [[speaker, text], …] from "Speaker: text" lines. */
    public function lines(): array
    {
        return collect(preg_split("/\r?\n/", $this->transcript, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($l) => str_contains($l, ':') ? array_map('trim', explode(':', $l, 2)) : ['', trim($l)])->all();
    }
}
