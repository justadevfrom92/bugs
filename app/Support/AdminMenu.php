<?php

namespace App\Support;

use App\Models\ContactMessage;
use App\Models\JobRun;
use App\Models\WorkItem;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/** Builds an app's sidebar from config('admin.apps.<key>.menu') for the admin layout. */
class AdminMenu
{
    /** App key of the current page, from the route name ("corral.customers.show" → "corral"). */
    public static function currentApp(): ?string
    {
        $key = Str::before((string) Route::currentRouteName(), '.');

        return config()->has('admin.apps.'.$key) ? $key : null;
    }

    /** @return array<string, array<int, array{label: string, url: string, active: bool, badge: int|null}>> */
    public static function sections(string $app): array
    {
        $current = (string) Route::currentRouteName();
        $currentTable = request()->route('table');
        $sections = [];

        foreach (config("admin.apps.$app.menu") as $heading => $items) {
            if ($items === 'reference_tables') {
                $items = collect(config('admin.reference_tables'))
                    ->map(fn ($t, $key) => [$t[0], 'sheriff.data.show', null, ['table' => $key]])->values()->all();
            }
            foreach ($items as $item) {
                $params = $item[3] ?? [];
                $sections[$heading][] = [
                    'label' => $item[0],
                    'route' => $item[1],
                    'url' => route($item[1], $params),
                    'exact' => $current === $item[1] && (! $params || $params['table'] === $currentTable),
                    'badge' => isset($item[2]) ? self::badge($item[2]) : null,
                ];
            }
        }

        // Highlight the exact item, or else the item from the same area (customers.show → customers.index)
        $hasExact = collect($sections)->flatten(1)->contains('exact', true);
        $area = Str::beforeLast($current, '.');
        foreach ($sections as &$items) {
            foreach ($items as &$it) {
                $it['active'] = $hasExact ? $it['exact'] : Str::beforeLast($it['route'], '.') === $area && Str::endsWith($it['route'], '.index');
            }
        }

        return $sections;
    }

    public static function badge(string $key): ?int
    {
        $n = match ($key) {
            'queues' => WorkItem::open()->count(),
            'messages' => ContactMessage::open()->count(),
            'failed_jobs' => self::failedJobs(),
            default => 0,
        };

        return $n ?: null;
    }

    /** Number of jobs whose most recent run failed. */
    public static function failedJobs(): int
    {
        return collect(array_keys(config('admin.jobs')))
            ->filter(fn ($cmd) => JobRun::where('command', $cmd)->latest('started_at')->value('status') === 'Failed')
            ->count();
    }
}
