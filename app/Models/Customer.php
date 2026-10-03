<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'autopay' => 'boolean', 'paperless' => 'boolean', 'peak_perks' => 'boolean',
            'balance' => 'float', 'stars' => 'integer', 'start_date' => 'date',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'account';
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('paid_on');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class)->latest('billed_on');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class)->latest()->latest('id');
    }

    /** Pill color for the status, from config('admin.customer_statuses'). */
    public function statusTone(): string
    {
        return config('admin.customer_statuses.'.$this->status, '');
    }
}
