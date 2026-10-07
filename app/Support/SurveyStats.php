<?php

namespace App\Support;

use App\Models\Survey;
use App\Models\SurveyCategory;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use Illuminate\Support\Collection;

/** Results for Rodeo → Surveys: per question, per category and for the whole survey. */
class SurveyStats
{
    /**
     * @param  Collection<int, SurveyResponse>  $responses
     * @return array<string, mixed> stats for one question
     */
    public static function question(SurveyQuestion $q, Collection $responses): array
    {
        $answers = $responses->map(fn ($r) => $r->answer($q))->filter(fn ($a) => $a !== null)->values();
        $n = $answers->count();
        $out = ['q' => $q, 'count' => $n, 'skipped' => $responses->count() - $n, 'rows' => [], 'avg' => null, 'nps' => null, 'texts' => collect()];

        switch ($q->type) {
            case 'rating':
                $nums = $answers->map(fn ($a) => (int) $a);
                $out['avg'] = $n ? round($nums->avg(), 1) : null;
                $out['nps'] = self::nps($nums);
                $out['bands'] = ['Detractors (0–6)' => $nums->filter(fn ($v) => $v <= 6)->count(), 'Passives (7–8)' => $nums->filter(fn ($v) => $v >= 7 && $v <= 8)->count(), 'Promoters (9–10)' => $nums->filter(fn ($v) => $v >= 9)->count()];
                $out['rows'] = self::rows($q->choices(), $nums->map(fn ($v) => (string) $v), $n);
                break;
            case 'scale':
                $choices = $q->choices();
                $scores = $answers->map(fn ($a) => array_search($a, $choices, true))->filter(fn ($i) => $i !== false)->map(fn ($i) => $i + 1);
                $out['avg'] = $scores->count() ? round($scores->avg(), 2) : null;
                $out['of'] = count($choices);
                $out['top'] = $scores->count() ? (int) round(100 * $scores->filter(fn ($s) => $s >= count($choices) - 1)->count() / $scores->count()) : null;
                $out['rows'] = self::rows($choices, $answers, $n);
                break;
            case 'multi':
                $picked = $answers->flatMap(fn ($a) => (array) $a);
                $out['rows'] = self::rows($q->choices(), $picked, $n);   // % of people who answered, so it can add up past 100
                break;
            case 'text':
                $out['texts'] = $responses->filter(fn ($r) => $r->answer($q) !== null)->sortByDesc('created_at')->values();
                break;
            default:   // single, yes_no
                $out['rows'] = self::rows($q->choices(), $answers, $n);
        }

        return $out;
    }

    /**
     * Every question's stats, grouped by category in the order they appear in the survey.
     *
     * @return Collection<string, array{category: ?SurveyCategory, questions: list<array>}>
     */
    public static function byCategory(Survey $survey, Collection $responses): Collection
    {
        $groups = collect();
        foreach ($survey->questions as $q) {
            $key = (string) ($q->survey_category_id ?? 0);
            if (! $groups->has($key)) {
                $groups[$key] = ['category' => $q->category, 'questions' => []];
            }
            $g = $groups[$key];
            $g['questions'][] = self::question($q, $responses);
            $groups[$key] = $g;
        }

        return $groups;
    }

    /** @param  Collection<int, int>  $scores  0–10 answers */
    public static function nps(Collection $scores): ?int
    {
        $n = $scores->count();

        return $n ? (int) round(100 * ($scores->filter(fn ($v) => $v >= 9)->count() - $scores->filter(fn ($v) => $v <= 6)->count()) / $n) : null;
    }

    /** A survey's NPS across its responses (its first 0–10 question), or null. */
    public static function surveyNps(Survey $survey, ?Collection $responses = null): ?int
    {
        $q = $survey->questions->firstWhere('type', 'rating');
        if (! $q) {
            return null;
        }
        $responses ??= $survey->responses;

        return self::nps($responses->map(fn ($r) => $r->answer($q))->filter(fn ($a) => $a !== null)->map(fn ($a) => (int) $a)->values());
    }

    /** @return list<array{label: string, count: int, pct: int}> */
    private static function rows(array $choices, Collection $answers, int $base): array
    {
        $counts = $answers->map(fn ($a) => (string) $a)->countBy();
        $labels = array_values(array_unique([...array_map('strval', $choices), ...$counts->keys()->all()]));

        return array_map(fn ($l) => ['label' => $l, 'count' => (int) ($counts[$l] ?? 0), 'pct' => $base ? (int) round(100 * ($counts[$l] ?? 0) / $base) : 0], $labels);
    }
}
