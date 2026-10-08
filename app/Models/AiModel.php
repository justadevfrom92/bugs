<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A model Deputy's agents can use: a Claude model, or a custom model downloaded to the local runtime. */
class AiModel extends Model
{
    use RecordsHistory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['checked_at' => 'datetime', 'size_gb' => 'float'];
    }

    public function agents(): HasMany
    {
        return $this->hasMany(AiAgent::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(AiConversation::class);
    }

    public function isLocal(): bool
    {
        return $this->provider === 'local';
    }

    public function providerLabel(): string
    {
        return config('deputy.providers.'.$this->provider, $this->provider);
    }
}
