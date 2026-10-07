<?php

namespace App\Support;

use App\Models\Bill;
use App\Models\ContactLog;
use App\Models\Customer;
use App\Models\CustomerFile;
use App\Models\CustomerProduct;
use App\Models\DataItem;
use App\Models\ErcotTransaction;
use App\Models\LedgerEntry;
use App\Models\Note;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\ServiceAddress;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * The original Corral item models (config/items.php). Each model's fields are
 * read from the app's own record where it has one (payments, bills, emails,
 * products…) and from data_items otherwise; fields the app doesn't track are
 * kept as "extras" on a data_items row tied to the record.
 */
class Items
{
    /** Ticket models open the existing ticket pages: model => log key (null = the account ticket). */
    public const TICKET_PAGES = ['ticket_cart_model' => null, 'TicketProductLog_model' => 'products', 'TicketErcotLog_model' => 'ercot',
        'TicketAttributeLog_model' => 'attributes', 'TicketContentLog_model' => 'contents', 'TicketContactLog_model' => 'emails'];

    /** Log tickets that collect items whose parent is the account itself: log key => models. */
    public const LOG_ITEMS = [
        'payments' => ['ItemPayment_model', 'ItemPayaccountCredit_model', 'ItemCredit_model', 'ItemDebit_model'],
        'files' => ['ItemFileBill_model', 'ItemFileEfl_model', 'ItemFileWelcomePacket_model', 'ItemBucketUsage_model', 'ItemForecastModel_model'],
        'logins' => ['ItemMyaccountLogin_model', 'ItemDevice_model'],
        'notes' => ['ItemNote_model'],
    ];

    /** The original items on a log ticket (or on the account ticket when $log is null). */
    public static function forTicket(Customer $c, ?string $log): Collection
    {
        return self::onTicket(self::forCustomer($c), $log);
    }

    /** Filter forCustomer() rows to one ticket. */
    public static function onTicket(Collection $all, ?string $log): Collection
    {
        if ($log === null) {
            return $all->filter(fn ($i) => in_array('TicketCart_model', $i['parents'], true))->values();
        }
        $ticket = array_search($log, self::TICKET_PAGES, true);

        return $all->filter(fn ($i) => in_array($i['model'], self::LOG_ITEMS[$log] ?? [], true) || ($ticket && in_array($ticket, $i['parents'], true)))->values();
    }

    public static function def(string $model): array
    {
        abort_unless(config()->has('items.models.'.$model), 404);

        return config('items.models.'.$model);
    }

    /** @return array<string, array> item models (not tickets) */
    public static function itemModels(): array
    {
        return array_filter(config('items.models'), fn ($m) => $m['source'] !== 'ticket');
    }

    // ---------- Finding records ----------

    /** All of one model's records for an account. */
    public static function forModel(string $model, Customer $c): Collection
    {
        $d = self::def($model);

        return match ($d['source']) {
            'payment' => $c->payments()->get(),
            'note' => $c->notes()->get(),
            'bill' => $c->bills()->get(),
            'credit', 'debit' => $c->ledger()->where('kind', $d['source'])->get(),
            'email' => self::emails($c->contactLogs()->getQuery(), $model)->get(),
            'ercot' => $c->ercotTransactions()->whereIn('trans_type', $d['types'])->get(),
            'product' => $c->products()->whereIn('product', self::productNames($model))->get(),
            'paymethod' => $c->paymentMethods()->get(),
            'file' => $c->files()->where('kind', $d['kind'])->get(),
            'address' => $c->addresses()->get(),
            'person', 'postal' => collect([$c]),
            'login' => $c->username ? collect([$c]) : collect(),
            'plan' => $c->plan_id ? collect([$c]) : collect(),
            'stored' => DataItem::where('model', $model)->whereNull('record_id')->where('customer_id', $c->id)->get(),
            default => collect(),
        };
    }

    public static function find(string $model, int $id): Model
    {
        $d = self::def($model);
        $r = match ($d['source']) {
            'payment' => Payment::find($id),
            'note' => Note::find($id),
            'bill' => Bill::find($id),
            'credit', 'debit' => LedgerEntry::where('kind', $d['source'])->find($id),
            'email' => ContactLog::where('channel', 'Email')->find($id),
            'ercot' => ErcotTransaction::whereIn('trans_type', $d['types'])->find($id),
            'product' => CustomerProduct::whereIn('product', self::productNames($model))->find($id),
            'paymethod' => PaymentMethod::find($id),
            'file' => CustomerFile::where('kind', $d['kind'])->find($id),
            'address' => ServiceAddress::find($id),
            'person', 'postal', 'login', 'plan', 'ticket' => Customer::find($id),
            'stored' => DataItem::where('model', $model)->whereNull('record_id')->find($id),
            default => null,
        };
        abort_unless($r, 404);

        return $r;
    }

    public static function customer(Model $r): ?Customer
    {
        return $r instanceof Customer ? $r : $r->customer;
    }

    /** Every item on an account, for the account and log tickets' Child Items. */
    public static function forCustomer(Customer $c): Collection
    {
        $c->loadMissing(['plan', 'market']);
        $rows = collect();
        foreach (self::itemModels() as $model => $d) {
            foreach (self::forModel($model, $c) as $r) {
                $rows->push(['model' => $model, 'id' => $r->getKey(), 'label' => $d['label'], 'summary' => self::summary($model, $r),
                    'created' => self::created($r), 'parents' => $d['parents'], 'url' => route('corral.items.show', [$r->getKey(), $model])]);
            }
        }

        return $rows->sortByDesc('created')->values();
    }

    private static function emails($q, string $model)
    {
        $template = config('items.models.'.$model.'.template');
        $mapped = collect(config('items.models'))->pluck('template')->filter()->values()->all();

        return $q->where('channel', 'Email')->when($template, fn ($q) => $q->where('template', $template), fn ($q) => $q->whereNotIn('template', $mapped));
    }

    /** Product names stored on accounts for a product model (config/corral.php maps names to models). */
    public static function productNames(string $model): array
    {
        return array_keys(array_filter(config('corral.products'), fn ($m) => $m === $model));
    }

    // ---------- Field values ----------

    public static function created(Model $r): Carbon
    {
        return $r instanceof Customer ? $r->created_at : ($r->created_at ?? now());
    }

    public static function summary(string $model, Model $r): string
    {
        $money = fn ($v) => '$'.number_format((float) $v, 2);

        return (string) match (self::def($model)['source']) {
            'payment' => $money($r->amount).' · '.$r->status,
            'note' => Str::limit((string) $r->body, 60),
            'bill' => trim(($r->invoice ?: $r->reference).' '.$money($r->amount)),
            'credit', 'debit' => $r->description.' '.$money($r->amount),
            'email' => $r->template,
            'ercot' => $r->label,
            'product' => $r->product,
            'paymethod' => ($r->nickname ?: $r->type).' •••• '.$r->last4,
            'file' => $r->name,
            'address' => $r->street.', '.$r->city,
            'person' => $r->first_name ?: $r->name,
            'postal' => ($r->billing_state ?: 'TX'),
            'login' => 'Credentials',
            'plan' => $r->plan?->name,
            'stored' => $r->summary,
            default => '',
        };
    }

    /** Panel title, as the original showed it: "Email - <template>", "Product - AutoPay"… */
    public static function title(string $model, Model $r): string
    {
        return trim(self::def($model)['label'].' - '.self::summary($model, $r), ' -');
    }

    /** @return array<string, string> every original field, in order, with its display value (secrets masked) */
    public static function values(string $model, Model $r): array
    {
        $d = self::def($model);
        $mapped = self::mapped($model, $r);
        $extras = $d['source'] === 'stored' ? ($r->data ?? []) : (self::extras($model, $r)?->data ?? []);
        $out = [];
        foreach ($d['fields'] as $f) {
            $v = $f === 'id' ? $r->getKey() : (array_key_exists($f, $mapped) ? $mapped[$f] : ($extras[$f] ?? ''));
            $out[$f] = in_array($f, config('items.secret'), true) ? self::mask($f, $v) : self::text($v);
        }

        return $out;
    }

    /**
     * Events the record itself carries (sent, opened, paid, reversed…), shown in
     * Process Logs alongside what was logged.
     *
     * @return list<array{at: Carbon, action: string, text: string}>
     */
    public static function events(string $model, Model $r): array
    {
        $e = fn ($at, $action, $text) => $at ? ['at' => Carbon::parse($at), 'action' => $action, 'text' => $text] : null;

        return array_values(array_filter(match (self::def($model)['source']) {
            'email' => [$e($r->created_at, 'email', 'Email created'), $e($r->sent_at, 'email', 'Email sent'), $e($r->opened_at, 'email', 'Email opened'),
                $e($r->clicked_at, 'email', 'Link clicked'), $e($r->dropped_at, 'email', 'Email dropped')],
            'payment' => [$e($r->created_at, 'payment', 'Payment of $'.number_format($r->amount, 2).' recorded ('.$r->status.')'), $e($r->reversed_at, 'payment', 'Payment reversed')],
            'bill' => [$e($r->billed_on, 'bill', 'Bill generated'), $e($r->paid_on, 'bill', 'Bill paid')],
            'ercot' => [$e($r->trans_date, 'ercot', $r->trans_type.' '.$r->purpose.': '.$r->label.' ('.$r->status.')')],
            'product' => [$e($r->created_at, 'product', 'Product added'), $e($r->removed_at, 'product', 'Product removed')],
            'note' => [$e($r->created_at, 'note', 'Note added by '.$r->author)],
            'credit', 'debit' => [$e($r->created_at, $r->kind, ucfirst($r->kind).' entered ('.$r->status.')')],
            'stored' => [$e($r->created_at, 'item', 'Item created')],
            default => [],
        }));
    }

    /** The data_items row holding an app record's extra original fields. */
    public static function extras(string $model, Model $r): ?DataItem
    {
        return DataItem::where('model', $model)->where('record_id', $r->getKey())->first();
    }

    /** Fields that map straight onto a column of the app record and can be edited here: field => column. */
    public static function writable(string $model): array
    {
        return match (self::def($model)['source']) {
            'payment' => ['confirmation_number' => 'confirmation', 'source' => 'source'],
            'note' => ['note' => 'body', 'priority' => 'priority', 'category' => 'category', 'action' => 'action'],
            'bill' => ['bill_type' => 'bill_type'],
            'credit', 'debit' => ['description' => 'description'],
            'paymethod' => ['method_name' => 'nickname'],
            'file' => ['file_name' => 'name'],
            'address' => ['address_1' => 'street', 'city' => 'city', 'zip' => 'zip'],
            'person' => ['first_name' => 'first_name', 'last_name' => 'last_name', 'email' => 'email', 'phone_1' => 'phone', 'lang' => 'language'],
            'postal' => ['address_1' => 'billing_street', 'address_2' => 'billing_unit', 'city' => 'billing_city', 'state' => 'billing_state', 'zip' => 'billing_zip'],
            'login' => ['username' => 'username'],
            default => [],
        };
    }

    /** Fields shown but not editable here (taken from the account or changed through their own screens). */
    public static function readOnly(string $model, Model $r): array
    {
        $d = self::def($model);
        if (in_array($d['source'], ['stored', 'product'], true)) {
            return ['id', 'error_log', ...($d['source'] === 'product' ? ['name', 'active_date', 'inactive_date'] : [])];
        }

        return array_values(array_diff(array_keys(self::mapped($model, $r)), array_keys(self::writable($model))));
    }

    /** Save the edit form: mapped columns to the record, everything else to the item's own fields. */
    public static function save(string $model, Model $r, array $input): void
    {
        $d = self::def($model);
        $skip = [...self::readOnly($model, $r), 'id', ...config('items.secret')];
        // Only fields the form sent: a field left out keeps its value
        $fields = collect($d['fields'])->reject(fn ($f) => in_array($f, $skip, true) || ! array_key_exists($f, $input))->mapWithKeys(fn ($f) => [$f => $input[$f]])->all();

        if ($d['source'] === 'stored') {
            $r->update(['data' => array_merge($r->data ?? [], $fields)]);

            return;
        }
        if ($d['source'] === 'product') {
            $r->update(['data' => array_merge($r->data ?? [], $fields)]);

            return;
        }
        $cols = [];
        foreach (self::writable($model) as $f => $col) {
            if (array_key_exists($f, $fields)) {
                $cols[$col] = $fields[$f] ?? '';
                unset($fields[$f]);
            }
        }
        if ($cols) {
            $r->update($cols);
        }
        $existing = self::extras($model, $r);
        $extras = array_filter(array_merge($existing?->data ?? [], $fields), fn ($v) => $v !== null && $v !== '');
        if ($extras || $existing) {
            DataItem::updateOrCreate(['model' => $model, 'record_id' => $r->getKey()],
                ['customer_id' => self::customer($r)?->id, 'data' => $extras, 'summary' => self::summary($model, $r)]);
        }
    }

    private static function text(mixed $v): string
    {
        return match (true) {
            $v === null => '',
            is_bool($v) => $v ? 'y' : 'n',
            $v instanceof \DateTimeInterface => $v->format('Y-m-d'),
            is_array($v) => json_encode($v, JSON_UNESCAPED_SLASHES),
            default => (string) $v,
        };
    }

    private static function mask(string $field, mixed $v): string
    {
        $v = (string) ($v ?? '');
        if ($v === '') {
            return '';
        }

        return in_array($field, ['ssn', 'cc_acct'], true) ? '•••• '.substr($v, -4) : '(set — hidden)';
    }

    /** Original field => value, from the app's own record. */
    private static function mapped(string $model, Model $r): array
    {
        $d = self::def($model);
        $c = self::customer($r);
        $yn = fn ($b) => $b ? 'y' : 'n';
        $date = fn ($v) => $v ? Carbon::parse($v)->format('Y-m-d') : '';
        $money = fn ($v) => $v === null ? '' : number_format((float) $v, 2, '.', '');

        return match ($d['source']) {
            'payment' => ['amount' => $money($r->amount), 'status' => $r->status, 'confirmation_number' => $r->confirmation,
                'confirmation_number_stripe' => $r->method === 'Card' ? $r->reference : '', 'confirmation_reverse' => $r->reversed_at ? 'REV-'.$r->id : '',
                'card_id' => $r->payment_method_id, 'reversed_timestamp' => $r->reversed_at?->timestamp, 'source' => $r->source, 'payment_date' => $date($r->paid_on),
                'transaction_id_seq' => $r->id, 'customer_type' => $c?->type],
            'note' => ['user_id' => $r->user_id, 'username' => $r->author, 'note' => $r->body, 'priority' => $r->priority, 'category' => $r->category, 'action' => $r->action],
            'bill' => ['file_name' => 'bill-'.($r->invoice ?: $r->reference).'.pdf', 'file_type' => 'pdf', 'status' => 'processed', 'invoice_number' => $r->invoice ?: $r->reference,
                'invoice_date' => $date($r->billed_on), 'due_date' => $date($r->due_on), 'uan1' => $c?->esiid, 'start_date' => $date($r->period_start), 'end_date' => $date($r->period_end),
                'usage_amt' => $r->kwh, 'uom' => 'KWH', 'billed_amount' => $money($r->amount), 'previous_payments' => $money($r->amount_paid), 'current_balance' => $money($r->balance_after),
                'amount_due' => $money($r->amount), 'paid_date' => $date($r->paid_on), 'processed' => 'y', 'ontime' => $r->ontime === null ? '' : $yn($r->ontime),
                'unpaid_amount' => $money(max(0, (float) $r->amount - (float) $r->amount_paid)), 'bill_type' => $r->bill_type, 'invoice_id' => $r->id],
            'credit', 'debit' => ['amount' => $money($r->amount), 'description' => $r->description, 'processed' => $yn($r->status === 'applied'), 'pending' => $yn($r->status === 'pending')],
            'email' => ['cmd' => json_encode(['date' => $date($r->sent_at ?? $r->created_at), 'Account Number' => $c?->account, 'Account Sub Status' => $c?->status, 'First Name' => $c?->first_name], JSON_UNESCAPED_SLASHES),
                'sent' => $yn($r->status === 'sent'), 'opened' => $yn($r->opened_at), 'clicked' => $yn($r->clicked_at), 'dropped' => $yn($r->dropped_at),
                'last_updated' => $r->updated_at?->timestamp, 'template_name' => $r->template, 'template_key' => self::key((string) $r->template),
                'fail_reason' => $r->status === 'not sent' ? 'SalesForce is not configured (Sheriff → APIs)' : '',
                'send_id' => $r->id.'_'.($r->sent_at ?? $r->created_at)?->timestamp, 'contact_key' => $c?->account, 'email' => $c?->email],
            'ercot' => ['outbound' => $yn($r->purpose === 'request'), 'processed' => 'y', 'document_set' => $r->trans_type, 'document_type' => Str::before((string) $r->trans_type, '_'),
                'document_purpose' => $r->purpose, 'document_tracking_number' => $r->tracking, 'transaction_date' => $date($r->trans_date), 'marketer_name' => config('brand.name'),
                'utility_name' => $c?->market?->name, 'ercot_name' => 'ERCOT', 'esi_id' => $r->esiid, 'commodity' => 'EL', 'action' => $r->label,
                'service_start_date' => $date($r->scheduled_on), 'move_in_date' => $date($r->scheduled_on), 'customer_name' => $c?->name, 'customer_name_service_address' => $c?->name,
                'customer_service_address_line_1' => $c?->address, 'customer_service_city' => $c?->city, 'customer_service_state' => 'TX', 'customer_service_zip_code' => $c?->zip,
                'service_address_zip_code' => $c?->zip, 'customer_phone_number_1' => $c?->phone, 'billing_address_line_1' => $c?->billing_street ?: $c?->address,
                'billing_city' => $c?->billing_city ?: $c?->city, 'billing_state' => $c?->billing_state ?: 'TX', 'billing_zip_code' => $c?->billing_zip ?: $c?->zip,
                'rejection_reason_code_description' => str_contains((string) $r->label, 'Reject') ? $r->status : '', 'status_reason_code_description' => $r->status,
                'meter_number' => $c?->meter_number, 'load_profile' => $c?->load_profile, 'meter_type' => $c?->meter_type, 'utility_rate_class' => $c?->rate_class],
            'product' => ['name' => $r->product, 'code' => Str::upper(Str::slug($r->product, '_')), 'agent' => $r->user?->name ?? 'System',
                'active_date' => $date($r->created_at), 'inactive_date' => $date($r->removed_at)] + collect($r->data ?? [])->map(fn ($v) => self::text($v))->all() + ['source' => 'Corral'],
            'paymethod' => ['address_1' => $c?->billing_street ?: $c?->address, 'city' => $c?->billing_city ?: $c?->city, 'state' => $c?->billing_state ?: 'TX', 'zip' => $c?->billing_zip ?: $c?->zip,
                'last_4' => $r->last4, 'method_name' => $r->nickname ?: $r->type, 'autopay' => $yn($r->autopay), 'status' => $r->removed_at ? 'removed' : 'active',
                'cc_acct' => $r->type === 'Card' ? $r->last4 : '', 'cc_exp_month' => $r->expires ? Str::before($r->expires, '/') : '', 'cc_exp_year' => $r->expires ? Str::after($r->expires, '/') : '',
                'card_type' => $r->vendor, 'name_on_card' => $c?->name],
            'file' => ['file_name' => $r->name, 'file_path' => $r->path, 'file_type' => Str::afterLast((string) $r->path, '.') ?: 'pdf', 'status' => 'generated', 'plan_id' => $c?->plan_id],
            'address' => ['address_1' => $r->street, 'city' => $r->city, 'state' => 'TX', 'zip' => $r->zip, 'market_id' => $c?->market_id, 'uan1' => $r->esiid, 'esiid' => $r->esiid,
                'start_date' => $date($r->ordered_at), 'service_start' => $date($r->service_start), 'service_end' => $date($r->service_end), 'load_zone' => $c?->load_zone,
                'billing_same' => $yn(! $c?->billing_street)],
            'person' => ['first_name' => $r->first_name, 'last_name' => $r->last_name, 'email' => $r->email, 'phone_1' => $r->phone, 'sms' => $yn($r->phone_type === 'mobile'),
                'lang' => $r->language, 'ssn' => $r->ssn_last4, 'communication_method' => 'email', 'customer_name' => $r->name, 'ter_username' => $r->username,
                'credit_level' => $r->tec_score, 'ip' => $r->ip, 'ip_state' => $r->ip_location, 'site_id' => 1, 'allow_marketing' => $yn($r->marketing_opt_in), 'person_id' => $r->id,
                'phone_type' => $r->phone_type],
            'postal' => ['address_1' => $r->billing_street ?: $r->address, 'address_2' => $r->billing_unit ?: $r->unit, 'city' => $r->billing_city ?: $r->city,
                'state' => $r->billing_state ?: 'TX', 'zip' => $r->billing_zip ?: $r->zip],
            'login' => ['username' => $r->username, 'password_hash' => $r->password ? 'set' : '', 'valid' => $yn((bool) $r->password)],
            'plan' => self::planFields($r),
            default => [],
        };
    }

    private static function planFields(Customer $c): array
    {
        $term = $c->planTerms()->where('status', 'current')->latest('id')->first() ?? $c->planTerms()->latest('id')->first();
        $plan = $c->plan;

        return ['rep_id' => $c->msid, 'market_id' => $c->market_id, 'move_switch' => $c->move_switch, 'move_switch_date' => $c->requested_start?->format('Y-m-d'),
            'plan_id' => $plan?->id, 'plan_name' => $plan?->name, 'plan_energy_charge' => $term?->energy_charge, 'plan_rate' => $term?->rate_2000, 'plan_rate_elec_2000' => $term?->rate_2000,
            'plan_etf' => $plan?->etf, 'plan_term' => $plan?->term, 'plan_partner_plan_id' => $plan?->internal, 'deposit_amt' => number_format((float) $c->deposit_due, 2, '.', ''),
            'balance_amt' => number_format((float) $c->balance, 2, '.', ''), 'commodity' => 'electric', 'promo_code' => $c->promo_code,
            'annual_usage' => (string) $c->bills()->where('billed_on', '>=', now()->subYear())->sum('kwh'),
            'contract_start_date' => $term?->contract_start?->format('Y-m-d'), 'contract_end_date' => $term?->contract_end?->format('Y-m-d'), 'meter_id' => $c->meter_number];
    }

    /** A stable template key for an email template name. */
    private static function key(string $template): string
    {
        $h = md5('template:'.$template);

        return substr($h, 0, 8).'-'.substr($h, 8, 4).'-'.substr($h, 12, 4).'-'.substr($h, 16, 4).'-'.substr($h, 20, 12);
    }
}
