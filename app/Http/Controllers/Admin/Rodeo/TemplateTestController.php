<?php

namespace App\Http\Controllers\Admin\Rodeo;

use App\Http\Controllers\Controller;
use App\Mail\TemplateTest;
use App\Models\ContactLog;
use App\Models\Customer;
use App\Models\EmailTemplate;
use App\Models\EmailTestSend;
use App\Models\Market;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Email Templates → Send a Test. Really sends the saved template, marked [TEST],
 * to the people picked: typed addresses, account numbers, your Corral bookmarks,
 * an admin team (role), or a category of customers.
 */
class TemplateTestController extends Controller
{
    /** A test never goes to more people than this. */
    public const MAX = 25;

    public const MODES = [
        'emails' => 'Email addresses',
        'accounts' => 'Account numbers',
        'bookmarks' => 'My bookmarked accounts',
        'team' => 'An admin team',
        'category' => 'A customer category',
    ];

    /** Mailers that only write to the log; nothing reaches an inbox until MAIL_MAILER is set in .env. */
    public static function delivers(): bool
    {
        return ! in_array(config('mail.default'), ['log', 'array'], true);
    }

    public function send(Request $request, EmailTemplate $template): RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in(array_keys(self::MODES))],
            'emails' => ['required_if:mode,emails', 'nullable', 'string', 'max:2000'],
            'sample' => ['nullable', 'exists:customers,account'],
            'accounts' => ['required_if:mode,accounts', 'nullable', 'string', 'max:2000'],
            'role_id' => ['required_if:mode,team', 'nullable', 'exists:roles,id'],
            'status' => ['nullable', Rule::in(array_keys(config('admin.customer_statuses')))],
            'type' => ['nullable', Rule::in(['Residential', 'Small Business'])],
            'market_id' => ['nullable', 'exists:markets,id'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX],
        ]);
        [$recipients, $target] = $this->recipients($request, $data);
        if ($recipients->isEmpty()) {
            throw ValidationException::withMessages(['mode' => 'Nobody with an email address matches that.']);
        }
        if ($recipients->count() > self::MAX) {
            throw ValidationException::withMessages(['mode' => $recipients->count().' people match. A test goes to '.self::MAX.' at most; narrow it down.']);
        }

        $delivers = self::delivers();
        $log = [];
        foreach ($recipients as $r) {
            /** @var Customer $c */
            $c = $r['customer'];
            $subject = '[TEST] '.$template->renderSubject($c);
            $body = $template->render($c);
            try {
                Mail::to($r['email'], $r['name'])->send(new TemplateTest($subject, $body));
                $status = $delivers ? 'sent' : 'logged';
                $error = null;
            } catch (\Throwable $e) {
                $status = 'failed';
                $error = substr($e->getMessage(), 0, 200);
            }
            // Customers see the test on their account's Contact Log
            if ($r['is_customer']) {
                ContactLog::create(['customer_id' => $c->id, 'channel' => 'Email', 'template' => 'Test: '.$template->name, 'body' => $body,
                    'status' => $status === 'sent' ? 'sent' : 'not sent', 'sent_at' => $status === 'sent' ? now() : null, 'user_id' => $request->user()->id]);
            }
            $log[] = array_filter(['email' => $r['email'], 'name' => $r['name'], 'account' => $r['is_customer'] ? $c->account : null, 'status' => $status, 'error' => $error]);
        }

        $statuses = array_unique(array_column($log, 'status'));
        $overall = count($statuses) === 1 ? $statuses[0] : 'partial';
        EmailTestSend::create(['email_template_id' => $template->id, 'user_id' => $request->user()->id, 'mode' => $data['mode'], 'target' => $target,
            'recipients' => $log, 'status' => $overall, 'created_at' => now()]);

        $n = count($log);
        $msg = match ($overall) {
            'sent' => "Test sent to $n ".str('recipient')->plural($n).'.',
            'logged' => "Test written to the mail log for $n ".str('recipient')->plural($n).' — not delivered, because MAIL_MAILER in .env is "'.config('mail.default').'". Set it to smtp (or ses, postmark…) with its MAIL_* settings to deliver.',
            'failed' => 'The test could not be sent: '.($log[0]['error'] ?? 'mail error').'. Check the MAIL_* settings in .env.',
            default => 'Test sent to some recipients; see Recent Tests for the ones that failed.',
        };

        return back()->with('test_result', ['ok' => $overall === 'sent', 'message' => $msg, 'mode' => $data['mode']])->withFragment('test');
    }

    /** @return array{0: Collection<int, array{email: string, name: ?string, customer: Customer, is_customer: bool}>, 1: ?string} */
    private function recipients(Request $request, array $data): array
    {
        // Fill-ins for people who aren't customers come from the sample account
        $sample = Customer::with('plan')->where('account', $data['sample'] ?? null)->first() ?? Customer::with('plan')->firstOrFail();
        $person = fn (string $email, ?string $name) => ['email' => $email, 'name' => $name, 'customer' => $sample, 'is_customer' => false];
        $customer = fn (Customer $c) => ['email' => $c->email, 'name' => $c->name, 'customer' => $c, 'is_customer' => true];
        $list = fn (?string $s) => collect(preg_split('/[\s,;]+/', (string) $s, -1, PREG_SPLIT_NO_EMPTY))->unique()->values();

        switch ($data['mode']) {
            case 'emails':
                $emails = $list($data['emails']);
                $bad = $emails->reject(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL));
                if ($bad->isNotEmpty()) {
                    throw ValidationException::withMessages(['emails' => 'Not an email address: '.$bad->implode(', ')]);
                }

                return [$emails->map(fn ($e) => $person($e, null)), $emails->implode(', ')];
            case 'accounts':
                $accounts = $list($data['accounts']);
                $found = Customer::with('plan')->whereIn('account', $accounts)->get();
                $missing = $accounts->diff($found->pluck('account'));
                if ($missing->isNotEmpty()) {
                    throw ValidationException::withMessages(['accounts' => 'No account '.$missing->implode(', ')]);
                }

                return [$found->filter(fn ($c) => $c->email)->map($customer)->values(), $accounts->implode(', ')];
            case 'bookmarks':
                return [$request->user()->bookmarks()->with('plan')->whereNotNull('email')->get()->map($customer)->values(), 'Bookmarks of '.$request->user()->name];
            case 'team':
                $role = Role::findOrFail($data['role_id']);

                return [User::where('role_id', $role->id)->where('active', true)->orderBy('name')->get()->map(fn ($u) => $person($u->email, $u->name)), $role->name.' team'];
            default:
                $q = Customer::with('plan')->whereNotNull('email')
                    ->when($data['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
                    ->when($data['type'] ?? null, fn ($q, $t) => $q->where('type', $t))
                    ->when($data['market_id'] ?? null, fn ($q, $m) => $q->where('market_id', $m));
                $target = collect([$data['status'] ?? 'Any status', $data['type'] ?? null, isset($data['market_id']) ? Market::find($data['market_id'])?->name : null])->filter()->implode(' · ');

                return [$q->inRandomOrder()->limit((int) ($data['limit'] ?? 5))->get()->map($customer)->values(), $target];
        }
    }
}
