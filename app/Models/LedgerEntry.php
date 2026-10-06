<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A pending credit or debit on an account. */
class LedgerEntry extends Model
{
    use RecordsHistory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['amount' => 'float'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** [model name, log group] for History. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function historyName(): array
    {
        return [$this->kind === 'credit' ? 'ItemCredit_model' : 'ItemDebit_model', 'Payments'];
    }

    public function historyLabel(): string
    {
        return ucfirst($this->kind).' $'.number_format($this->amount, 2).' — '.$this->description;
    }
}
