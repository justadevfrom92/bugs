<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A saved card or bank account. */
class PaymentMethod extends Model
{
    use RecordsHistory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['autopay' => 'boolean', 'removed_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** [model name, log group] for History. */
    public function historyName(): array
    {
        return ['ItemPayaccountCredit_model', 'Payments'];
    }

    public function label(): string
    {
        return $this->type.' - '.$this->last4.($this->nickname ? ' ('.$this->nickname.')' : '');
    }

    public function historyLabel(): string
    {
        return $this->label();
    }
}
