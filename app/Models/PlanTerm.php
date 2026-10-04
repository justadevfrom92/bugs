<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One row of the Plan Change Log. */
class PlanTerm extends Model
{
    use RecordsHistory;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['ordered_at' => 'datetime', 'contract_start' => 'date', 'contract_end' => 'date', 'energy_charge' => 'float', 'rate_2000' => 'float'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** [model name, log group] for History. */
    public function historyName(): array
    {
        return ['ItemElectricity_model', 'Products'];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function historyLabel(): string
    {
        return ($this->plan?->name ?? 'Plan').' ('.$this->rate_class.')';
    }
}
