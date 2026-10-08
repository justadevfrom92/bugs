<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** An AI agent: what it does (activity), the model it runs on, and its instructions. */
class AiAgent extends Model
{
    use RecordsHistory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(AiModel::class, 'ai_model_id');
    }

    public function fallback(): BelongsTo
    {
        return $this->belongsTo(AiModel::class, 'fallback_model_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(AiConversation::class);
    }

    public function activityLabel(): string
    {
        return config('deputy.activities.'.$this->activity, $this->activity);
    }
}
