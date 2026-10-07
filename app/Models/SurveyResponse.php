<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyResponse extends Model
{
    public const UPDATED_AT = null;

    public const SOURCES = ['email' => 'Email link', 'website' => 'Website', 'my-account' => 'My Account'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['answers' => 'array', 'created_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    /** This response's answer to a question (string, list for Pick any, or null). */
    public function answer(SurveyQuestion|int $q): mixed
    {
        $a = ($this->answers ?? [])[(string) (is_int($q) ? $q : $q->id)] ?? null;

        return $a === '' || $a === [] ? null : $a;
    }

    /** The response's 0–10 recommend score, if the survey asks one. */
    public function nps(): ?int
    {
        $q = $this->survey?->questions->firstWhere('type', 'rating');
        $a = $q ? $this->answer($q) : null;

        return $a === null ? null : (int) $a;
    }
}
