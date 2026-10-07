<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A ready-made question (Rodeo → Surveys → Question Bank) that can be added to any survey. */
class SurveyBankQuestion extends Model
{
    use RecordsHistory;

    protected $guarded = ['id'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(SurveyCategory::class, 'survey_category_id');
    }

    public function answerSet(): BelongsTo
    {
        return $this->belongsTo(SurveyAnswerSet::class, 'survey_answer_set_id');
    }

    public function uses(): HasMany
    {
        return $this->hasMany(SurveyQuestion::class);
    }
}
