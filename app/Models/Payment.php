<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use RecordsHistory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['amount' => 'float', 'paid_on' => 'date', 'reversed_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
