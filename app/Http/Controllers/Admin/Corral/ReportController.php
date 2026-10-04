<?php

namespace App\Http\Controllers\Admin\Corral;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Note;
use App\Models\Phonecall;
use App\Models\ReportRun;
use App\Models\User;
use App\Support\Xlsx;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Corral → Reports. Each report shows on screen or as a summary, downloads as CSV or XLS, or runs in the backend; runs are kept under Recent Results. */
class ReportController extends Controller
{
    /** The original's output choices. "backend" runs after the page returns and saves an .xlsx under Recent Results. */
    private const OUTPUTS = ['screen', 'summary', 'csv', 'csv-summary', 'xls', 'xls-summary', 'backend'];

    public function orders(Request $request)
    {
        $statuses = array_keys(config('admin.customer_statuses'));
        $f = $request->validate([
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
            'statuses' => ['nullable', 'array'],
            'statuses.*' => [Rule::in($statuses)],
            'output' => ['nullable', Rule::in(self::OUTPUTS)],
        ]);
        $f += ['start' => now()->startOfYear()->toDateString(), 'end' => today()->toDateString(), 'statuses' => $statuses, 'output' => 'screen'];

        $orders = null;
        if ($request->has('run')) {
            $fetch = fn () => Customer::with(['plan', 'market'])
                ->whereDate('created_at', '>=', $f['start'])->whereDate('created_at', '<=', $f['end'])
                ->whereIn('status', $f['statuses'])->orderBy('created_at')->get();
            $detail = fn ($orders) => [['Account', 'Created', 'Status', 'Exception', 'Customer', 'Type', 'Phone', 'Email', 'Address', 'City', 'Zip', 'Market', 'ESIID', 'Plan', 'Source'],
                $orders->map(fn ($c) => [$c->account, $c->created_at->toDateString(), $c->status, $c->exception, $c->name, $c->type, $c->phone, $c->email,
                    $c->address, $c->city, $c->zip, $c->market?->name, $c->esiid, $c->plan?->internal, $c->source])];
            $summary = fn ($orders) => [['Status', 'Orders'], $orders->countBy('status')->map(fn ($n, $s) => [$s, $n])->values()];
            if ($response = $this->deliver($request, 'orders', $f, $fetch, $detail, $summary)) {
                return $response;
            }
            $orders = $fetch();
            $this->remember($request, 'orders', $f, $orders->count());
        }

        return view('admin.corral.report', ['f' => $f, 'statuses' => $statuses, 'orders' => $orders, 'recent' => $this->recent($request, 'orders')]);
    }

    public function notes(Request $request)
    {
        $priorities = [...config('corral.priorities'), 'System'];
        $f = $request->validate([
            'user' => ['nullable', 'integer', 'exists:users,id'],
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
            'contains' => ['nullable', 'string', 'max:100'],
            'priorities' => ['nullable', 'array'],
            'priorities.*' => [Rule::in($priorities)],
            'output' => ['nullable', Rule::in(self::OUTPUTS)],
        ]);
        $f += ['user' => null, 'start' => today()->subDays(30)->toDateString(), 'end' => today()->toDateString(), 'contains' => null, 'priorities' => $priorities, 'output' => 'screen'];

        $notes = null;
        if ($request->has('run')) {
            $wanted = $f['priorities'];
            $fetch = fn () => Note::with('customer')
                ->whereDate('created_at', '>=', $f['start'])->whereDate('created_at', '<=', $f['end'])
                ->when($f['user'], fn ($q, $id) => $q->where('user_id', $id))
                ->when($f['contains'], fn ($q, $text) => $q->where('body', 'like', '%'.addcslashes($text, '%_\\').'%'))
                ->where(function ($q) use ($wanted) {
                    // "System" = notes written by the system rather than an agent
                    $q->whereIn('priority', array_diff($wanted, ['System']));
                    if (in_array('System', $wanted, true)) {
                        $q->orWhereNull('user_id');
                    }
                })
                ->latest()->get();
            $detail = fn ($notes) => [['Date', 'Account', 'Customer', 'Author', 'Category', 'Action', 'Priority', 'Note'],
                $notes->map(fn ($n) => [$n->created_at->format('Y-m-d H:i'), $n->customer?->account, $n->customer?->name, $n->author, $n->category, $n->action, $n->priority, $n->body])];
            $summary = fn ($notes) => [['Author', 'Category', 'Notes'], $this->notesSummary($notes)->map(fn ($r) => array_values($r))];
            if ($response = $this->deliver($request, 'notes', $f, $fetch, $detail, $summary)) {
                return $response;
            }
            $notes = $fetch();
            $this->remember($request, 'notes', $f, $notes->count());
        }

        return view('admin.corral.reports.notes', ['f' => $f, 'priorities' => $priorities, 'users' => User::orderBy('name')->get(['id', 'name']),
            'notes' => $notes, 'summary' => $notes ? $this->notesSummary($notes) : null, 'recent' => $this->recent($request, 'notes')]);
    }

    public function phonecalls(Request $request)
    {
        $f = $request->validate([
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
            'account' => ['nullable', 'string', 'max:20'],
            'agent' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
            'output' => ['nullable', Rule::in(self::OUTPUTS)],
        ]);
        $f += ['start' => now()->startOfYear()->toDateString(), 'end' => today()->toDateString(), 'account' => null, 'agent' => null, 'phone' => null, 'output' => 'screen'];

        $calls = null;
        if ($request->has('run')) {
            $digits = preg_replace('/\D/', '', (string) $f['phone']);
            $fetch = fn () => Phonecall::with(['customer', 'user'])
                ->whereDate('started_at', '>=', $f['start'])->whereDate('started_at', '<=', $f['end'])
                ->when($f['account'], fn ($q, $a) => $q->whereHas('customer', fn ($c) => $c->where('account', trim($a))))
                ->when($f['agent'], fn ($q, $a) => $q->where('agent_id', trim($a)))
                ->latest('started_at')->get()
                // Phone numbers are stored formatted; compare digits only
                ->when($digits, fn ($c) => $c->filter(fn ($call) => str_contains(preg_replace('/\D/', '', $call->phone), $digits))->values());
            $detail = fn ($calls) => [['Started', 'Direction', 'Phone', 'Account', 'Customer', 'Agent Id', 'Agent', 'Seconds', 'Disposition'],
                $calls->map(fn ($c) => [$c->started_at->format('Y-m-d H:i'), $c->direction, $c->phone, $c->customer?->account, $c->customer?->name, $c->agent_id, $c->user?->name, $c->duration_sec, $c->disposition])];
            $summary = fn ($calls) => [['Agent Id', 'Calls', 'Inbound', 'Outbound', 'Minutes'], $this->callsSummary($calls)->map(fn ($r) => array_values($r))];
            if ($response = $this->deliver($request, 'phonecalls', $f, $fetch, $detail, $summary)) {
                return $response;
            }
            $calls = $fetch();
            $this->remember($request, 'phonecalls', $f, $calls->count());
        }

        return view('admin.corral.reports.phonecalls', ['f' => $f, 'calls' => $calls, 'summary' => $calls ? $this->callsSummary($calls) : null,
            'recent' => $this->recent($request, 'phonecalls')]);
    }

    private function notesSummary(Collection $notes): Collection
    {
        return $notes->groupBy(fn ($n) => ($n->author ?? 'System').'|'.($n->category ?? '—'))
            ->map(fn ($g, $k) => ['author' => explode('|', $k)[0], 'category' => explode('|', $k)[1], 'notes' => $g->count()])
            ->sortBy(['author', 'category'])->values();
    }

    private function callsSummary(Collection $calls): Collection
    {
        return $calls->groupBy(fn ($c) => $c->agent_id ?? '—')
            ->map(fn ($g, $agent) => ['agent' => $agent, 'calls' => $g->count(), 'inbound' => $g->where('direction', 'inbound')->count(),
                'outbound' => $g->where('direction', 'outbound')->count(), 'minutes' => (int) round($g->sum('duration_sec') / 60)])
            ->sortKeys()->values();
    }

    /**
     * File outputs: CSV or XLS (detail or summary) download now; Backend saves an .xlsx
     * for later. Returns null for the on-screen outputs.
     */
    private function deliver(Request $request, string $report, array $f, \Closure $fetch, \Closure $detail, \Closure $summary)
    {
        $output = $f['output'];
        if (in_array($output, ['screen', 'summary'], true)) {
            return null;
        }

        if ($output === 'backend') {
            $run = $this->remember($request, $report, $f, 0, 'running');
            // Runs after the response is sent, so a big report doesn't hold up the page
            app()->terminating(function () use ($run, $fetch, $detail) {
                try {
                    $rows = $fetch();
                    [$header, $data] = $detail($rows);
                    $file = 'reports/'.$run->report.'-'.$run->id.'.xlsx';
                    Storage::disk('local')->put($file, Xlsx::build($header, $data, ucfirst($run->report)));
                    $run->update(['status' => 'done', 'file' => $file, 'rows' => $rows->count()]);
                } catch (\Throwable $e) {
                    report($e);
                    $run->update(['status' => 'failed']);
                }
            });

            return back()->with('status', 'Report is running in the background. It will appear under Recent Results with a download link.');
        }

        $rows = $fetch();
        $this->remember($request, $report, $f, $rows->count());
        [$header, $data] = str_ends_with($output, 'summary') ? $summary($rows) : $detail($rows);
        $name = $report.(str_ends_with($output, 'summary') ? '-summary' : '').'-'.$f['start'].'-to-'.$f['end'];

        return str_starts_with($output, 'xls')
            ? response(Xlsx::build($header, $data, ucfirst($report)), 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="'.$name.'.xlsx"',
            ])
            : $this->csv($name, $header, $data);
    }

    /** Recent Results → download a Backend report (your own runs only). */
    public function download(Request $request, ReportRun $run)
    {
        abort_unless($run->user_id === $request->user()->id && $run->file && Storage::disk('local')->exists($run->file), 404);

        return Storage::disk('local')->download($run->file, basename($run->file));
    }

    private function remember(Request $request, string $report, array $f, int $rows, string $status = 'done'): ReportRun
    {
        return ReportRun::create(['user_id' => $request->user()->id, 'report' => $report, 'params' => array_filter($f, fn ($v) => $v !== null && $v !== []),
            'rows' => $rows, 'status' => $status, 'created_at' => now()]);
    }

    private function recent(Request $request, string $report): Collection
    {
        return ReportRun::where('user_id', $request->user()->id)->where('report', $report)->latest('created_at')->latest('id')->limit(10)->get();
    }

    private function csv(string $name, array $header, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $header);
            foreach ($rows as $row) {
                // Keep spreadsheet apps from running cell text as a formula
                fputcsv($out, array_map(fn ($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'".$v : $v, $row));
            }
            fclose($out);
        }, $name.'.csv', ['Content-Type' => 'text/csv']);
    }
}
