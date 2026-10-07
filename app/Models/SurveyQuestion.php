<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyQuestion extends Model
{
    public $timestamps = false;

    public const TYPES = [
        'rating' => 'NPS 0–10',
        'scale' => 'Scale',
        'single' => 'Pick one',
        'multi' => 'Pick any',
        'yes_no' => 'Yes / No',
        'text' => 'Written answer',
    ];

    /** Types whose answers come from a list (an answer set or the question's own options). */
    public const LISTED = ['scale', 'single', 'multi'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['options' => 'array', 'required' => 'boolean'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(SurveyCategory::class, 'survey_category_id');
    }

    public function answerSet(): BelongsTo
    {
        return $this->belongsTo(SurveyAnswerSet::class, 'survey_answer_set_id');
    }

    /** @return list<string> the answers a respondent can pick */
    public function choices(): array
    {
        return match ($this->type) {
            'rating' => array_map('strval', range(0, 10)),
            'yes_no' => ['Yes', 'No'],
            'text' => [],
            default => array_values($this->options ?? []),
        };
    }
}
