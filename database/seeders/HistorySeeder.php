<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\HistoryItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * A realistic backlog of history for the FICTIONAL sample accounts, using the
 * original system's model names. Live activity is recorded automatically.
 */
class HistorySeeder extends Seeder
{
    private array $rows = [];

    public function run(): void
    {
        mt_srand(4242);
        $csr = User::where('email', 'csr@example.com')->value('id');

        foreach (Customer::with(['plan', 'market', 'payments', 'bills', 'notes'])->get() as $c) {
            $this->account($c, $csr);
        }
        $this->adminActivity();

        foreach (array_chunk($this->rows, 500) as $chunk) {
            HistoryItem::insert($chunk);
        }
    }

    private function add(?Customer $c, Carbon $at, string $model, string $group, string $action, string $summary, array $data = [], ?int $user = null, ?array $changes = null): void
    {
        $this->rows[] = [
            'customer_id' => $c?->id, 'user_id' => $user, 'model' => $model, 'group' => $group,
            'record_id' => (int) ($at->format('U').mt_rand(1000, 9999)), 'action' => $action, 'summary' => $summary,
            'data' => json_encode($data), 'changes' => $changes ? json_encode($changes) : null,
            'ip' => $user ? '10.0.0.'.mt_rand(10, 99) : '203.0.113.'.mt_rand(1, 254), 'created_at' => $at->format('Y-m-d H:i:s'),
        ];
    }

    private function account(Customer $c, ?int $csr): void
    {
        $t = Carbon::parse($c->created_at);
        $plan = $c->plan;
        $base = ['account' => $c->account, 'ticket' => $c->ticket];

        // Enrollment
        $this->add($c, $t, 'TicketCustomer_model', 'Account Attributes', 'created', 'Account created via '.$c->source, $base + ['status' => 'Submitted', 'source' => $c->source, 'customer_type' => $c->type === 'Residential' ? 'r' : 'c']);
        $this->add($c, $t->copy()->addSeconds(2), 'ItemPersonShopper_model', 'Account Attributes', 'created', 'Shopper '.$c->name, ['name' => $c->name, 'email' => $c->email, 'phone' => $c->phone, 'language' => 'en']);
        $this->add($c, $t->copy()->addSeconds(3), 'ItemLocationPostalUsa_model', 'Account Attributes', 'created', 'Service address '.$c->address.', '.$c->city.' '.$c->zip, ['street' => $c->address, 'city' => $c->city, 'state' => 'TX', 'zip' => $c->zip]);
        $this->add($c, $t->copy()->addSeconds(4), 'ItemLocationUtilityTexas_model', 'Account Attributes', 'created', 'ESIID '.$c->esiid.' · '.$c->market?->name, ['esiid' => $c->esiid, 'tdsp' => $c->market?->name, 'meter_type' => 'AMS', 'premise_type' => $c->type === 'Residential' ? 'Residential' : 'Small Non-Residential']);
        if ($plan) {
            $this->add($c, $t->copy()->addSeconds(5), 'ItemElectricityPartnerEnergytexasBrandEnergytexasTx_model', 'Products', 'created', 'Plan '.$plan->name.' ('.$plan->internal.')', ['plan' => $plan->internal, 'term' => $plan->term, 'etf' => $plan->etf, 'mrc' => $plan->mrc, 'green' => $plan->green]);
            $this->add($c, $t->copy()->addSeconds(6), 'ItemFileEfl_model', 'Billing & Files', 'created', 'Electricity Facts Label for '.$plan->internal, ['file' => 'efl-'.strtolower($plan->internal).'.pdf', 'plan' => $plan->internal]);
        }
        $this->add($c, $t->copy()->addSeconds(8), 'ItemProductReward_model', 'Products', 'created', 'Product Rangler Rewards', ['product' => 'Rangler Rewards', 'stars' => 0]);
        if ($c->autopay) {
            $this->add($c, $t->copy()->addSeconds(9), 'ItemProductAutopay_model', 'Products', 'created', 'Product AutoPay', ['product' => 'AutoPay', 'method' => 'Card']);
        }
        if ($c->paperless) {
            $this->add($c, $t->copy()->addSeconds(10), 'ItemProductPaperless_model', 'Products', 'created', 'Product Paperless Billing', ['product' => 'Paperless Billing']);
            $this->add($c, $t->copy()->addSeconds(11), 'ItemProductReward_model', 'Products', 'created', 'Paperless Signup +10 Stars', ['reason' => 'Paperless Signup', 'stars' => 10]);
        }
        $this->add($c, $t->copy()->addMinute(), 'ItemEmailEnergytexasAccepted_model', 'Emails', 'sent', 'Email: We got your order', ['to' => $c->email, 'template' => 'accepted']);

        // Credit + utility (EDI) transactions
        $this->add($c, $t->copy()->addMinutes(2), 'ItemAttribute_model', 'Account Attributes', 'created', 'Attribute credit_check = complete', ['attribute' => 'credit_check', 'value' => 'complete']);
        $this->add($c, $t->copy()->addHours(3), 'ItemErcot81405_model', 'EDI Transactions', 'sent', 'ERCOT Switch/MVI request sent', ['transaction' => '814_01', 'esiid' => $c->esiid, 'requested_date' => $t->copy()->addDays(5)->toDateString()]);
        $rejected = str_starts_with($c->status, 'Rejected');
        $this->add($c, $t->copy()->addDay()->addHours(2), 'ItemErcot81416_model', 'EDI Transactions', 'received',
            $rejected ? 'ERCOT reject: ESIID not eligible for switch' : 'ERCOT accept: switch scheduled',
            ['transaction' => '814_16', 'esiid' => $c->esiid, 'result' => $rejected ? 'R' : 'A']);
        if ($rejected) {
            return;
        }

        $this->add($c, $t->copy()->addDays(3), 'ItemEmailEnergytexasWelcome_model', 'Emails', 'sent', 'Email: Welcome to '.config('brand.name'), ['to' => $c->email, 'template' => 'welcome']);
        $this->add($c, $t->copy()->addDays(3)->addMinute(), 'ItemFileWelcomePacket_model', 'Billing & Files', 'created', 'Welcome packet generated', ['file' => 'welcome-'.$c->account.'.pdf']);
        $this->add($c, $t->copy()->addDays(5), 'ItemErcot81405_model', 'EDI Transactions', 'received', 'ERCOT 867_04 initial meter read', ['transaction' => '867_04', 'esiid' => $c->esiid]);
        $this->add($c, $t->copy()->addDays(5)->addHour(), 'TicketCustomer_model', 'Account Attributes', 'updated', 'Status changed', $base, null, ['status' => ['Submitted', 'Good - On Flow']]);

        // Monthly usage, bills, payments, stars
        foreach ($c->bills->sortBy('billed_on') as $b) {
            $d = Carbon::parse($b->billed_on);
            $this->add($c, $d->copy()->subDays(2), 'ItemBucketUsage_model', 'Usage', 'received', number_format($b->kwh).' kWh used in '.$d->copy()->subMonth()->format('F Y'), ['kwh' => $b->kwh, 'month' => $d->copy()->subMonth()->format('Y-m'), 'source' => '867_03']);
            $this->add($c, $d, 'ItemFileBill_model', 'Billing & Files', 'created', 'Bill '.$b->reference.' · $'.number_format($b->amount, 2), ['reference' => $b->reference, 'kwh' => $b->kwh, 'amount' => $b->amount, 'due' => $d->copy()->addDays(16)->toDateString()]);
            $this->add($c, $d->copy()->addMinutes(5), 'ItemEmailSalesforce_model', 'Emails', 'sent', 'Email: Your bill is ready', ['to' => $c->email, 'campaign' => 'bill_ready']);
        }
        foreach ($c->payments->sortBy('paid_on') as $p) {
            $d = Carbon::parse($p->paid_on)->setTime(mt_rand(7, 21), mt_rand(0, 59));
            $this->add($c, $d, 'ItemPayment_model', 'Payments', 'created', 'Payment - '.$p->status.': $'.number_format($p->amount, 2), ['reference' => $p->reference, 'amount' => $p->amount, 'status' => $p->status, 'method' => $p->method, 'source' => $p->source, 'confirmation_number' => (string) mt_rand(5000000000, 5999999999)]);
            if ($p->status === 'Success') {
                $stars = round($p->amount * 0.05, 1);
                $this->add($c, $d->copy()->addSecond(), 'ItemProductReward_model', 'Products', 'created', 'Bill Payment +'.$stars.' Stars', ['reason' => 'Bill Payment', 'stars' => $stars]);
                $this->add($c, $d->copy()->addMinute(), 'ItemEmailEnergytexasPaymentMade_model', 'Emails', 'sent', 'Email: Thanks for your payment', ['to' => $c->email, 'amount' => $p->amount]);
            }
        }

        // Account activity: logins, content, attributes
        $days = max(1, (int) $t->diffInDays(now()));
        for ($i = 0, $n = mt_rand(3, 9); $i < $n; $i++) {
            $d = $t->copy()->addDays(mt_rand(6, $days))->setTime(mt_rand(6, 23), mt_rand(0, 59));
            if ($d->isFuture()) {
                continue;
            }
            $this->add($c, $d, 'ItemMyaccountLogin_model', 'Logins', 'logged', 'My Account sign-in', ['device' => ['iPhone', 'Android', 'Windows', 'Mac'][mt_rand(0, 3)], 'method' => 'password']);
            if (mt_rand(0, 2) === 0) {
                $this->add($c, $d->copy()->addMinutes(2), 'ItemContentViewed_model', 'Content', 'viewed', 'Item Content Viewed', ['content_id' => (string) mt_rand(1600000000, 1699999999), 'times_shown' => mt_rand(1, 6), 'times_clicked' => mt_rand(0, 3)]);
            }
        }
        if (mt_rand(0, 3) === 0) {
            $key = array_rand(['ev' => 1, 'pool' => 1, 'solar' => 1]);
            $this->add($c, $t->copy()->addDays(mt_rand(10, $days)), 'ItemAttribute_model', 'Account Attributes', 'created', 'Attribute '.$key.' = y', ['attribute' => $key, 'value' => 'y']);
        }
        if ($c->peak_perks || mt_rand(0, 4) === 0) {
            $this->add($c, $t->copy()->addDays(mt_rand(10, $days)), 'ItemProductPeakPerk_model', 'Products', 'created', 'Product Peak Perks', ['product' => 'Peak Perks', 'device' => 'Smart thermostat']);
        }
        foreach ($c->notes as $note) {
            if ($note->author !== 'System') {
                $this->add($c, Carbon::parse($note->created_at), 'ItemNote_model', 'Notes', 'created', 'Note: '.mb_substr($note->body, 0, 80), ['author' => $note->author, 'body' => $note->body], $csr);
            }
        }
    }

    /** Some past admin activity so each admin user's history page has entries. */
    private function adminActivity(): void
    {
        $users = User::pluck('id', 'email');
        $start = now()->subDays(20);
        $events = [
            ['pricing@example.com', 'Rate_model', 'Rates updated for TX-E-ONCOR (15 plans)', ['market' => 'TX-E-ONCOR']],
            ['pricing@example.com', 'TermDiscount_model', 'Term discount 24 · ONCOR changed', null, ['discount' => ['0.75', '0.80']]],
            ['pricing@example.com', 'ByopProduct_model', 'Peak Perks — rate adj changed', null, ['rate_adj' => ['-0.30', '-0.40']]],
            ['marketing@example.com', 'ContentBlock_model', 'Home - Hero — html changed', null, ['html' => ['(previous hero copy)', '(new hero copy)']]],
            ['marketing@example.com', 'Page_model', 'Page created: Keep Your Cool in Extreme Heat', ['path' => 'get-to-learnin/how-to-keep-your-cool-in-extreme-heat']],
            ['admin@example.com', 'User_model', 'User created: Finance Demo', ['email' => 'finance@example.com', 'role' => 'Finance']],
            ['admin@example.com', 'Role_model', 'Pricing — perms changed', null, ['perms' => ['["lando"]', '["lando","astro"]']]],
            ['finance@example.com', 'ReferenceTable_model', 'Deposit Thresholds saved (4 rows)', ['table' => 'deposit-thresholds']],
        ];
        foreach ($events as $i => $e) {
            $at = $start->copy()->addDays($i * 2)->setTime(9 + $i, 15);
            $this->add(null, $at, $e[1], 'Admin Changes', isset($e[4]) ? 'updated' : 'created', $e[2], $e[3] ?? [], $users[$e[0]] ?? null, $e[4] ?? null);
        }
        foreach ($users as $email => $id) {
            for ($d = 1; $d <= 6; $d++) {
                $this->add(null, now()->subDays($d * 3)->setTime(8, mt_rand(0, 59)), 'AdminLogin_model', 'Logins', 'logged', 'Signed in to the admin', ['user_agent' => 'Mozilla/5.0'], $id);
            }
        }
    }
}
