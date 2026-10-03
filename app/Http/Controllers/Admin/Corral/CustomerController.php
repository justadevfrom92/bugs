<?php

namespace App\Http\Controllers\Admin\Corral;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\ReferenceRow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    /** Customer Search */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::in(['Residential', 'Small Business'])],
            'status' => ['nullable', 'string'],
            'exception' => ['nullable', 'string'],
            'limit' => ['nullable', 'integer', Rule::in([0, 10, 25, 50, 100])],
        ]);
        $limit = (int) ($filters['limit'] ?? 25);

        $query = Customer::with(['plan', 'market'])->latest();
        $this->applyFilters($query, $filters);
        $total = (clone $query)->count();
        $results = $limit ? $query->limit($limit)->get() : $query->get();

        $searched = collect($filters)->except('limit')->filter()->isNotEmpty();

        return view('admin.corral.customers.index', [
            'filters' => $filters + ['limit' => $limit],
            'results' => $results,
            'total' => $total,
            'searched' => $searched,
            'bookmarks' => $request->user()->bookmarks()->with(['plan', 'market'])->get(),
            'stats' => [
                'accounts' => Customer::count(),
                'onFlow' => Customer::where('status', 'Good - On Flow')->count(),
                'pending' => Customer::where(fn ($q) => $q->where('status', 'like', 'Pending%')->orWhere('status', 'Submitted'))->count(),
                'exceptions' => Customer::whereNotNull('exception')->count(),
            ],
        ]);
    }

    public static function applyFilters(Builder $query, array $f): void
    {
        if (! empty($f['type'])) {
            $query->where('type', $f['type']);
        }
        if (! empty($f['status'])) {
            $query->where('status', $f['status']);
        }
        if (($f['exception'] ?? '') === '*') {
            $query->whereNotNull('exception');
        } elseif (! empty($f['exception'])) {
            $query->where('exception', $f['exception']);
        }
        if (! empty($f['q'])) {
            $q = trim($f['q']);
            $digits = preg_replace('/\D/', '', $q);
            $query->where(function (Builder $w) use ($q, $digits) {
                foreach (['name', 'email', 'account', 'ticket', 'esiid', 'address', 'city', 'zip'] as $col) {
                    $w->orWhere($col, 'like', '%'.$q.'%');
                }
                if (strlen($digits) >= 4) {
                    $w->orWhere(DB::raw("REPLACE(REPLACE(REPLACE(REPLACE(phone, '(', ''), ')', ''), ' ', ''), '-', '')"), 'like', '%'.$digits.'%');
                }
            });
        }
    }

    public function show(Request $request, Customer $customer): View
    {
        $customer->load(['plan', 'market', 'payments', 'bills', 'notes']);

        return view('admin.corral.customers.show', [
            'c' => $customer,
            'bookmarked' => $request->user()->bookmarks()->whereKey($customer->id)->exists(),
            'dispositions' => ReferenceRow::where('table_key', 'note-dispositions')->orderBy('position')->get()->pluck('cells.1')->filter(),
            'canRefund' => Gate::allows('refunds'),
        ]);
    }

    public function updateStatus(Request $request, Customer $customer): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(config('admin.customer_statuses')))]]);
        if ($data['status'] !== $customer->status) {
            $this->note($request, $customer, 'Status changed from '.$customer->status.' to '.$data['status'].'.');
            $customer->update(['status' => $data['status']]);
        }

        return redirect(route('corral.customers.show', $customer).'#service')->with('status', 'Status updated');
    }

    public function bookmark(Request $request, Customer $customer): RedirectResponse
    {
        $result = $request->user()->bookmarks()->toggle($customer->id);

        return back()->with('status', $result['attached'] ? 'Bookmarked' : 'Bookmark removed');
    }

    public function addNote(Request $request, Customer $customer): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'disposition' => ['nullable', 'string', 'max:100'],
        ]);
        $this->note($request, $customer, $data['body'], $data['disposition'] ?? null);

        return redirect(route('corral.customers.show', $customer).'#notes')->with('status', 'Note added');
    }

    /** Needs the "refunds" right. Marks the payment reversed and puts the amount back on the balance. */
    public function reversePayment(Request $request, Payment $payment): RedirectResponse
    {
        Gate::authorize('refunds');
        abort_unless($payment->status === 'Success', 422, 'Only successful payments can be reversed.');

        DB::transaction(function () use ($request, $payment) {
            $payment->update(['status' => 'Reversed', 'reversed_at' => now(), 'reversed_by' => $request->user()->id]);
            $payment->customer->increment('balance', $payment->amount);
            $this->note($request, $payment->customer, 'Payment '.$payment->reference.' for $'.number_format($payment->amount, 2).' reversed.', 'Payment arrangement');
        });

        return redirect(route('corral.customers.show', $payment->customer).'#payments')->with('status', 'Payment reversed');
    }

    private function note(Request $request, Customer $customer, string $body, ?string $disposition = null): void
    {
        $customer->notes()->create([
            'user_id' => $request->user()->id,
            'author' => $request->user()->name,
            'disposition' => $disposition,
            'body' => $body,
        ]);
    }
}
