<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A customer survey (Rodeo → Surveys), answered on the website at /survey/{slug}. */
class Survey extends Model
{
    use RecordsHistory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['opens_on' => 'date', 'closes_on' => 'date'];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(SurveyQuestion::class)->orderBy('position');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    /** open | scheduled | ended | closed */
    public function state(): string
    {
        return match (true) {
            $this->status === 'closed' => 'closed',
            $this->opens_on && $this->opens_on->isFuture() => 'scheduled',
            $this->closes_on && $this->closes_on->endOfDay()->isPast() => 'ended',
            default => 'open',
        };
    }

    public function stateLabel(): string
    {
        return ['open' => 'Open', 'scheduled' => 'Opens '.$this->opens_on?->format('M j'), 'ended' => 'Ended '.$this->closes_on?->format('M j'), 'closed' => 'Closed'][$this->state()];
    }

    public function isOpen(): bool
    {
        return $this->state() === 'open';
    }
}
