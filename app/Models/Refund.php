<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A refund of a credit balance or deposit: requested, then approved (needs the refunds right) and paid. */
class Refund extends Model
{
    use RecordsHistory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['amount' => 'float', 'decided_at' => 'datetime', 'paid_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function historyName(): array
    {
        return ['ItemRefund_model', 'Payments'];
    }

    public function historyLabel(): string
    {
        return 'Refund $'.number_format($this->amount, 2).' ('.$this->status.')';
    }
}
