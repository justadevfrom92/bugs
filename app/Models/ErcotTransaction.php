<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** An EDI transaction with ERCOT (814, 650, 867…). */
class ErcotTransaction extends Model
{
    use RecordsHistory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['trans_date' => 'date', 'scheduled_on' => 'date'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** [model name, log group] for History. */
    public function historyName(): array
    {
        return ['ItemErcot'.str_replace('_', '', $this->trans_type).'_model', 'EDI Transactions'];
    }

    public function historyLabel(): string
    {
        return 'ERCOT '.$this->trans_type.' '.$this->label;
    }
}
