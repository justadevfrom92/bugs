<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A topic survey questions are filed under (Rodeo → Surveys → Categories), e.g. Billing & Payments. */
class SurveyCategory extends Model
{
    use RecordsHistory;

    protected $guarded = ['id'];

    public function bankQuestions(): HasMany
    {
        return $this->hasMany(SurveyBankQuestion::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(SurveyQuestion::class);
    }
}
