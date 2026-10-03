<?php

namespace App\Http\Controllers\Admin\Sheriff;

use App\Http\Controllers\Controller;
use App\Models\JobRun;
use App\Models\ReferenceRow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** API integrations, scheduled jobs and reference data tables. */
class SystemController extends Controller
{
    /** Shows only whether each .env value is set. Secret values are never displayed. */
    public function integrations(): View
    {
        $integrations = collect(config('admin.integrations'))->map(fn ($i) => $i + [
            'set' => collect($i['env'])->map(fn ($v) => filled($v))->all(),
            'configured' => collect($i['env'])->every(fn ($v) => filled($v)),
        ]);

        return view('admin.sheriff.integrations', ['integrations' => $integrations]);
    }

    public function jobs(): View
    {
        $jobs = collect(config('admin.jobs'))->map(fn ($job, $command) => [
            'command' => $command, 'name' => $job[0], 'schedule' => $job[1],
            'last' => JobRun::where('command', $command)->latest('started_at')->first(),
        ]);

        return view('admin.sheriff.jobs', ['jobs' => $jobs, 'history' => JobRun::latest('started_at')->limit(15)->get()]);
    }

    public function runJob(Request $request): RedirectResponse
    {
        $data = $request->validate(['command' => ['required', Rule::in(array_keys(config('admin.jobs')))]]);
        Artisan::call($data['command'], ['--user' => $request->user()->id]);
        $run = JobRun::where('command', $data['command'])->latest('id')->first();

        return back()->with('status', config('admin.jobs.'.$data['command'])[0].': '.($run?->status ?? 'done').($run?->message ? ' — '.$run->message : ''));
    }

    public function table(string $table): View
    {
        abort_unless(config()->has('admin.reference_tables.'.$table), 404);
        [$name, $columns] = config('admin.reference_tables.'.$table);

        return view('admin.sheriff.table', [
            'key' => $table, 'name' => $name, 'columns' => $columns,
            'rows' => ReferenceRow::where('table_key', $table)->orderBy('position')->get(),
        ]);
    }

    /** Replaces the table's rows with what was submitted. */
    public function updateTable(Request $request, string $table): RedirectResponse
    {
        abort_unless(config()->has('admin.reference_tables.'.$table), 404);
        [$name, $columns] = config('admin.reference_tables.'.$table);
        $data = $request->validate(['rows' => ['array'], 'rows.*' => ['array'], 'rows.*.*' => ['nullable', 'string', 'max:500']]);

        DB::transaction(function () use ($table, $columns, $data) {
            ReferenceRow::where('table_key', $table)->delete();
            foreach (array_values($data['rows'] ?? []) as $i => $cells) {
                $cells = array_map(fn ($c) => $cells[$c] ?? '', array_keys($columns));
                if (collect($cells)->filter(fn ($v) => trim((string) $v) !== '')->isEmpty()) {
                    continue;
                }
                ReferenceRow::create(['table_key' => $table, 'position' => $i, 'cells' => array_map(fn ($v) => (string) $v, $cells)]);
            }
        });

        return back()->with('status', $name.' saved');
    }
}
