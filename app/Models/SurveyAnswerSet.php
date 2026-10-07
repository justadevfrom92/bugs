<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A reusable list of answers (Rodeo → Surveys → Answer Sets), e.g. a five-point
 * satisfaction scale. Scales are ordered and scored 1..n in results.
 */
class SurveyAnswerSet extends Model
{
    use RecordsHistory;

    public const GROUPS = ['Satisfaction', 'Agreement', 'Likelihood', 'Frequency', 'Ease', 'Yes / No', 'Channels', 'Other'];

    public const TYPES = ['scale' => 'Scale (ordered, scored)', 'single' => 'Pick one', 'multi' => 'Pick any'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['options' => 'array'];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(SurveyQuestion::class);
    }

    public function bankQuestions(): HasMany
    {
        return $this->hasMany(SurveyBankQuestion::class);
    }
}
