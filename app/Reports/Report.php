<?php

namespace App\Reports;

use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * One report: what it reads (its model), the filters it takes, and its rows.
 * Corral's report screens and Walker both use these classes; the list of
 * reports is config('walker.reports').
 */
abstract class Report
{
    /** Filters: name => [label, type (date|text|select|multi), options for select/multi]. */
    abstract public function filters(): array;

    /** @return Collection the records the report covers */
    abstract public function query(array $f): Collection;

    abstract public function columns(): array;

    abstract public function row($record): array;

    /** Summary output: [header, rows]. Default: count by the first column. */
    public function summary(Collection $records): array
    {
        $first = $this->columns()[0];

        return [[$first, 'Rows'], $records->map(fn ($r) => $this->row($r)[0])->countBy()->map(fn ($n, $v) => [$v, $n])->values()];
    }

    /** Validation rules for the filters. */
    public function rules(): array
    {
        $rules = [];
        foreach ($this->filters() as $name => [$label, $type]) {
            $options = $this->filters()[$name][2] ?? [];
            $rules[$name] = match ($type) {
                'date' => ['nullable', 'date'],
                'select' => ['nullable', Rule::in(array_keys($options))],
                'multi' => ['nullable', 'array'],
                default => ['nullable', 'string', 'max:100'],
            };
            if ($type === 'multi') {
                $rules[$name.'.*'] = [Rule::in(array_keys($options))];
            }
        }
        if (isset($rules['end'], $rules['start'])) {
            $rules['end'][] = 'after_or_equal:start';
        }

        return $rules;
    }

    /** Filter values used when none are given. */
    public function defaults(): array
    {
        return ['start' => now()->startOfYear()->toDateString(), 'end' => today()->toDateString()];
    }

    /** [header, rows] for the file outputs. */
    public function detail(Collection $records): array
    {
        return [$this->columns(), $records->map(fn ($r) => $this->row($r))->values()];
    }

    public static function make(string $key): static
    {
        $def = config('walker.reports.'.$key);
        abort_unless($def, 404);

        return app($def['class']);
    }

    public static function definition(string $key): array
    {
        return config('walker.reports.'.$key) + ['key' => $key];
    }
}
