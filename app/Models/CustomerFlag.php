<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A flag on an account (VIP, fraud, returned mail…). */
class CustomerFlag extends Model
{
    use RecordsHistory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['removed_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** [model name, log group] for History. */
    public function historyName(): array
    {
        return ['ItemFlag_model', 'Account Attributes'];
    }
}
