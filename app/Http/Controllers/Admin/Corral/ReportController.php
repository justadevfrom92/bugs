<?php

namespace App\Http\Controllers\Admin\Corral;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /** Orders Report: on screen, summary, or CSV download. */
    public function orders(Request $request)
    {
        $statuses = array_keys(config('admin.customer_statuses'));
        $f = $request->validate([
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
            'statuses' => ['nullable', 'array'],
            'statuses.*' => [Rule::in($statuses)],
            'output' => ['nullable', Rule::in(['screen', 'summary', 'csv'])],
            'run' => ['nullable'],
        ]);
        $f += ['start' => now()->startOfYear()->toDateString(), 'end' => today()->toDateString(), 'statuses' => $statuses, 'output' => 'screen'];

        $orders = null;
        if ($request->has('run')) {
            $query = Customer::with(['plan', 'market'])
                ->whereDate('created_at', '>=', $f['start'])->whereDate('created_at', '<=', $f['end'])
                ->whereIn('status', $f['statuses'])->orderBy('created_at');

            if ($f['output'] === 'csv') {
                return $this->csv($query->get(), $f);
            }
            $orders = $query->get();
        }

        return view('admin.corral.report', ['f' => $f, 'statuses' => $statuses, 'orders' => $orders]);
    }

    private function csv($orders, array $f): StreamedResponse
    {
        return response()->streamDownload(function () use ($orders) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Account', 'Created', 'Status', 'Exception', 'Customer', 'Type', 'Phone', 'Email', 'Address', 'City', 'Zip', 'Market', 'ESIID', 'Plan', 'Source']);
            foreach ($orders as $c) {
                fputcsv($out, [$c->account, $c->created_at->toDateString(), $c->status, $c->exception, $c->name, $c->type, $c->phone, $c->email,
                    $c->address, $c->city, $c->zip, $c->market?->name, $c->esiid, $c->plan?->internal, $c->source]);
            }
            fclose($out);
        }, 'orders-'.$f['start'].'-to-'.$f['end'].'.csv', ['Content-Type' => 'text/csv']);
    }
}
