<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** An email or text sent to a group of customers (Rodeo → Campaigns). */
class Campaign extends Model
{
    use RecordsHistory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['audience' => 'array', 'sent_at' => 'datetime'];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ContactLog::class);
    }

    /** Customers the audience filters select. Marketing only goes to customers who opted in. */
    public function audienceQuery(): Builder
    {
        $a = $this->audience ?? [];

        return Customer::query()->where('marketing_opt_in', true)
            ->when($this->channel === 'SMS', fn ($q) => $q->where('phone_type', 'mobile'))
            ->when($this->channel === 'Email', fn ($q) => $q->whereNotNull('email'))
            ->when($a['statuses'] ?? null, fn ($q, $s) => $q->whereIn('status', $s))
            ->when($a['type'] ?? null, fn ($q, $t) => $q->where('type', $t))
            ->when($a['market_id'] ?? null, fn ($q, $m) => $q->where('market_id', $m))
            ->when($a['product'] ?? null, fn ($q, $p) => $q->whereHas('products', fn ($x) => $x->where('product', $p)->whereNull('removed_at')))
            ->when($a['without_product'] ?? null, fn ($q, $p) => $q->whereDoesntHave('products', fn ($x) => $x->where('product', $p)->whereNull('removed_at')))
            ->when($a['contract_ends_within'] ?? null, fn ($q, $days) => $q->whereHas('planTerms', fn ($x) => $x->where('status', 'current')->whereBetween('contract_end', [today(), today()->addDays((int) $days)])));
    }
}
