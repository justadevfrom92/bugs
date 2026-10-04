<?php

namespace App\Http\Controllers\Admin\Corral;

use App\Http\Controllers\Controller;
use App\Models\ContactLog;
use App\Models\Customer;
use App\Services\AccountActions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Corral → SMS: text conversations with customers. Sending goes through the SMS integration in .env. */
class SmsController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));
        $digits = preg_replace('/\D/', '', $q);

        // One row per customer: their latest text
        $latest = ContactLog::where('channel', 'SMS')->selectRaw('max(id) as id')->groupBy('customer_id');
        $threads = ContactLog::whereIn('id', $latest)->with('customer')->latest('created_at')->get()
            ->when($q !== '', fn ($t) => $t->filter(fn ($l) => str_contains(strtolower($l->customer?->name ?? ''), strtolower($q))
                || ($digits !== '' && (str_contains(preg_replace('/\D/', '', (string) $l->phone), $digits) || str_contains((string) $l->customer?->account, $digits))))->values());
        $unanswered = $threads->where('direction', 'in')->count();

        $open = $request->query('account') ? Customer::where('account', $request->query('account'))->first() : null;

        return view('admin.corral.sms', [
            'q' => $q, 'threads' => $threads, 'unanswered' => $unanswered, 'open' => $open,
            'messages' => $open ? ContactLog::where('channel', 'SMS')->where('customer_id', $open->id)->oldest('created_at')->oldest('id')->get() : collect(),
            'ready' => collect(config('admin.integrations.sms.env'))->every(fn ($v) => filled($v)),
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'account' => ['required', 'string', 'exists:customers,account'],
            'message' => ['required', 'string', 'max:480'],
        ]);
        $c = Customer::where('account', $data['account'])->firstOrFail();
        abort_if(blank($c->phone), 422, 'This account has no phone number.');

        [$message] = DB::transaction(fn () => (new AccountActions($c, $request->user()))->run('send-text', ['message' => $data['message']]));

        return redirect()->route('corral.sms', ['account' => $c->account])->with('status', $message);
    }
}
