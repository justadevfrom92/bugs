<?php

namespace App\Http\Controllers\MyAccount;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\PlanGroup;
use App\Models\PlanTerm;
use App\Models\WorkItem;
use App\Services\AccountActions;
use App\Services\Catalog;
use App\Services\Products;
use App\Services\Rewards;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

/**
 * My Account: the customer's own view of their account. Changes go through the
 * same services Corral uses (AccountActions, Products, Rewards), so agents see
 * every change in the account's notes and history.
 */
class PortalController extends Controller
{
    /** Products customers can turn on and off themselves: url slug => [product, label, description] */
    public const SELF_SERVICE = [
        'autopay' => ['AutoPay', 'AutoPay', 'Pay your bill automatically on the due date with your default payment method.'],
        'paperless-billing' => ['Paperless Billing', 'Paperless Billing', 'Get your bill by email instead of by mail.'],
        'peak-perks' => ['Peak Perks', 'Peak Perks', 'Earn bill credits for using less electricity during peak demand events.'],
        'giddy-up' => ['Pick Your Due Date', 'Pick Your Due Date', 'Choose the day of the month your bill is due.'],
    ];

    private function c(Request $request): Customer
    {
        return $request->user('customer');
    }

    private function actions(Request $request): AccountActions
    {
        return new AccountActions($this->c($request), null, 'MyAccount');
    }

    public function dashboard(Request $request): View
    {
        $c = $this->c($request)->load(['plan', 'bills', 'paymentMethods']);

        return view('site.myaccount.dashboard', [
            'c' => $c,
            'lastBill' => $c->bills->sortByDesc('billed_on')->first(),
            'term' => $c->planTerms()->where('status', 'current')->first() ?? $c->planTerms()->latest('ordered_at')->first(),
            'usage' => $c->bills->sortBy('billed_on')->take(-12)->values(),
            'messages' => $c->contactLogs()->limit(3)->get(),
        ]);
    }

    public function bills(Request $request): View
    {
        $c = $this->c($request);

        return view('site.myaccount.bills', ['c' => $c, 'bills' => $c->bills()->orderByDesc('billed_on')->get(), 'payments' => $c->payments()->orderByDesc('paid_on')->limit(24)->get()]);
    }

    public function insights(Request $request): View
    {
        $c = $this->c($request);
        $bills = $c->bills()->orderBy('billed_on')->get();

        return view('site.myaccount.insights', ['c' => $c, 'bills' => $bills->take(-12)->values(), 'avg' => $bills->avg('kwh'), 'max' => max(1, (int) $bills->max('kwh'))]);
    }

    // ---------- Pay bill / payment methods ----------

    public function payForm(Request $request): View
    {
        $c = $this->c($request);

        return view('site.myaccount.pay', ['c' => $c, 'methods' => $c->paymentMethods()->whereNull('removed_at')->get()]);
    }

    public function pay(Request $request): RedirectResponse
    {
        $c = $this->c($request);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:10000'],
            'method' => ['required', Rule::exists('payment_methods', 'id')->where('customer_id', $c->id)->whereNull('removed_at')],
        ]);
        [$message] = DB::transaction(fn () => $this->actions($request)->run('make-payment', $data));

        return redirect()->route('myaccount.bills')->with('status', $message);
    }

    public function methods(Request $request): View
    {
        $c = $this->c($request);

        return view('site.myaccount.methods', ['c' => $c, 'methods' => $c->paymentMethods()->whereNull('removed_at')->get(),
            'cards' => collect(config('admin.integrations.stripe.env'))->every(fn ($v) => filled($v))]);
    }

    public function removeMethod(Request $request, PaymentMethod $method): RedirectResponse
    {
        abort_unless($method->customer_id === $this->c($request)->id, 404);
        $method->update(['removed_at' => now(), 'autopay' => false]);

        return back()->with('status', 'Payment method removed.');
    }

    public function defaultMethod(Request $request, PaymentMethod $method): RedirectResponse
    {
        $c = $this->c($request);
        abort_unless($method->customer_id === $c->id && ! $method->removed_at, 404);
        PaymentMethod::where('customer_id', $c->id)->update(['autopay' => false]);
        $method->update(['autopay' => true]);

        return back()->with('status', $method->label().' is now used for AutoPay.');
    }

    // ---------- AutoPay, Paperless, Peak Perks, Pick Your Due Date ----------

    public function product(Request $request, string $product): View
    {
        abort_unless(isset(self::SELF_SERVICE[$product]), 404);
        $c = $this->c($request);

        return view('site.myaccount.product', ['c' => $c, 'slug' => $product, 'p' => self::SELF_SERVICE[$product], 'on' => $c->hasProduct(self::SELF_SERVICE[$product][0]),
            'hasMethod' => $c->paymentMethods()->whereNull('removed_at')->exists()]);
    }

    public function toggleProduct(Request $request, string $product): RedirectResponse
    {
        abort_unless(isset(self::SELF_SERVICE[$product]), 404);
        $c = $this->c($request);
        [$name, $label] = self::SELF_SERVICE[$product];
        $on = $request->validate(['enroll' => ['required', 'boolean']])['enroll'];
        if ($on && $name === 'AutoPay' && ! $c->paymentMethods()->whereNull('removed_at')->exists()) {
            return back()->withErrors(['enroll' => 'Add a payment method before turning on AutoPay.']);
        }
        $on ? Products::add($c, $name) : Products::remove($c, $name);
        $c->notes()->create(['author' => 'Customer (MyAccount)', 'category' => 'Products', 'action' => ($on ? 'Add ' : 'Remove ').$label, 'priority' => 'Low',
            'body' => ($on ? 'Enrolled in ' : 'Un-enrolled from ').$label.' in My Account.']);

        return back()->with('status', $on ? 'You\'re enrolled in '.$label.'.' : $label.' is turned off.');
    }

    // ---------- Profile, password, authorized users, linked accounts ----------

    public function profile(Request $request): View
    {
        return view('site.myaccount.profile', ['c' => $this->c($request)]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $c = $this->c($request);
        $c->update($request->validate([
            'email' => ['required', 'email', 'max:200'],
            'phone' => ['required', 'regex:/^\D*(\d\D*){10}$/'],
            'phone_type' => ['required', Rule::in(['mobile', 'landline', 'voip'])],
            'language' => ['required', Rule::in(['English', 'Spanish'])],
            'billing_street' => ['required', 'string', 'max:200'],
            'billing_city' => ['required', 'string', 'max:100'],
            'billing_state' => ['required', 'string', 'size:2'],
            'billing_zip' => ['required', 'digits:5'],
        ]) + ['marketing_opt_in' => $request->boolean('marketing_opt_in')]);

        return back()->with('status', 'Profile saved.');
    }

    public function password(Request $request): RedirectResponse
    {
        $c = $this->c($request);
        $data = $request->validate(['current_password' => ['required'], 'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed']]);
        if (! Hash::check($data['current_password'], $c->password)) {
            return back()->withErrors(['current_password' => 'Your current password isn\'t right.']);
        }
        $c->update(['password' => $data['password']]);

        return back()->with('status', 'Password changed.');
    }

    public function authorizedUser(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'phone' => ['required', 'string', 'max:20']]);
        [$message] = $this->actions($request)->run('add-authorized-user', $data);

        return back()->with('status', $message);
    }

    public function removeAuthorizedUser(Request $request, int $index): RedirectResponse
    {
        $c = $this->c($request);
        $users = $c->authorized_users ?? [];
        abort_unless(isset($users[$index]), 404);
        $name = $users[$index]['name'];
        array_splice($users, $index, 1);
        $c->update(['authorized_users' => $users]);

        return back()->with('status', $name.' removed.');
    }

    public function linkAccount(Request $request): RedirectResponse
    {
        $c = $this->c($request);
        $data = $request->validate(['account' => ['required', 'string', 'max:20'], 'zip' => ['required', 'digits:5']]);
        $other = Customer::where('account', $data['account'])->where('zip', $data['zip'])->whereRaw('lower(email) = ?', [strtolower($c->email)])->first();
        if (! $other || $other->is($c)) {
            return back()->withErrors(['account' => 'To link an account it must have the same email as this one.']);
        }
        $c->update(['linked_accounts' => array_values(array_unique([...($c->linked_accounts ?? []), $other->account]))]);

        return back()->with('status', 'Account '.$other->account.' linked.');
    }

    // ---------- Plan: current plan, renew, transfer service ----------

    public function plan(Request $request, Catalog $catalog): View
    {
        $c = $this->c($request)->load('plan');
        $rates = $catalog->currentRates();
        $fee = $catalog->currentFees()[$c->market_id] ?? null;
        // Renewal offers: Lando → Plan Groups "renew" (business accounts: "business")
        $group = PlanGroup::where('slug', $c->type === 'Residential' ? 'renew' : 'business')->first();
        $offers = ($group ? $group->plans()->where('active', true)->where('term', '>', 1)->get() : collect())
            ->filter(fn ($p) => isset($rates[$p->id.'-'.$c->market_id]) && $fee)
            ->map(fn ($p) => ['plan' => $p, 'price' => Catalog::averagePrice($rates[$p->id.'-'.$c->market_id]->energy, $fee->per_kwh, $fee->per_bill, $p->mrc, 1000)])->values();

        return view('site.myaccount.plan', ['c' => $c, 'term' => $c->planTerms()->where('status', 'current')->first(),
            'pending' => $c->planTerms()->where('status', 'pending')->with('plan')->first(), 'offers' => $offers]);
    }

    public function renew(Request $request): RedirectResponse
    {
        $c = $this->c($request);
        $data = $request->validate(['plan_id' => ['required', Rule::exists('plans', 'id')->where('active', true)], 'agree' => ['accepted']]);
        $plan = Plan::find($data['plan_id']);
        $current = $c->planTerms()->where('status', 'current')->first();
        $start = $current?->contract_end && $current->contract_end->isFuture() ? $current->contract_end->copy()->addDay() : today()->addDay();
        DB::transaction(function () use ($c, $plan, $start) {
            PlanTerm::where('customer_id', $c->id)->where('status', 'pending')->delete();
            PlanTerm::create(['customer_id' => $c->id, 'plan_id' => $plan->id, 'rate_class' => now()->format('Ymd').'_F_'.$plan->internal, 'status' => 'pending',
                'ordered_at' => now(), 'contract_start' => $start, 'contract_end' => $start->copy()->addMonths($plan->term), 'energy_charge' => 0, 'rate_2000' => 0]);
            WorkItem::create(['queue' => 'unapplied-plan-changes', 'customer_id' => $c->id, 'summary' => 'Renewal from My Account: '.$plan->internal]);
            $c->notes()->create(['author' => 'Customer (MyAccount)', 'category' => 'Order', 'action' => 'Renewal', 'priority' => 'Low',
                'body' => 'Renewed to '.$plan->name.' ('.$plan->internal.') in My Account, starting '.$start->toDateString().'.']);
        });

        return redirect()->route('myaccount.plan')->with('status', 'You\'re renewed on '.$plan->name.', starting '.$start->format('M j, Y').'.');
    }

    public function transferForm(Request $request): View
    {
        return view('site.myaccount.transfer', ['c' => $this->c($request)]);
    }

    public function transfer(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'street' => ['required', 'string', 'max:200'], 'city' => ['required', 'string', 'max:100'], 'zip' => ['required', 'digits:5'],
            'esiid' => ['nullable', 'regex:/^\d{17,22}$/'], 'date' => ['required', 'date', 'after:today', 'before:+90 days'],
        ]);
        [$message] = DB::transaction(fn () => $this->actions($request)->run('transfer-service', $data + ['esiid' => $data['esiid'] ?? null]));

        return redirect()->route('myaccount.dashboard')->with('status', $message);
    }

    // ---------- Rewards, refer a friend, message center ----------

    public function rewards(Request $request): View
    {
        $c = $this->c($request);

        return view('site.myaccount.rewards', ['c' => $c, 'offers' => Rewards::offers(), 'history' => $c->starEntries()->latest()->limit(30)->get()]);
    }

    public function redeem(Request $request): RedirectResponse
    {
        $data = $request->validate(['offer' => ['required', 'integer', 'min:0']]);
        try {
            return back()->with('status', Rewards::redeem($this->c($request), (int) $data['offer']));
        } catch (RuntimeException $e) {
            return back()->withErrors(['offer' => $e->getMessage()]);
        }
    }

    public function refer(Request $request): View
    {
        $c = $this->c($request);

        return view('site.myaccount.refer', ['c' => $c, 'referrals' => $c->referral_code ? Customer::where('referred_by', $c->referral_code)->get(['first_name', 'status', 'created_at']) : collect()]);
    }

    public function messages(Request $request): View
    {
        return view('site.myaccount.messages', ['c' => $this->c($request), 'messages' => $this->c($request)->contactLogs()->limit(50)->get()]);
    }

    // ---------- QuickPay (no sign-in) ----------

    public function quickpay(Request $request): View
    {
        $c = null;
        if ($request->filled('account')) {
            $request->validate(['account' => ['required', 'string', 'max:20'], 'zip' => ['required', 'digits:5']]);
            $c = Customer::where('account', $request->query('account'))->where('zip', $request->query('zip'))->first();
        }

        return view('site.myaccount.quickpay', ['c' => $c, 'looked' => $request->filled('account')]);
    }
}
