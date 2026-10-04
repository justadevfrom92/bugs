<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A document on the account: EFL, YRAC, TOS, welcome packet, invoice or upload. */
class CustomerFile extends Model
{
    use RecordsHistory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** [model name, log group] for History. */
    public function historyName(): array
    {
        return ['ItemFile_model', 'Billing & Files'];
    }
}
