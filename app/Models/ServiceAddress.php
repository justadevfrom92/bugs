<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One row of the Address Change Log. */
class ServiceAddress extends Model
{
    use RecordsHistory;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['ordered_at' => 'datetime', 'service_start' => 'date', 'service_end' => 'date'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** [model name, log group] for History. */
    public function historyName(): array
    {
        return ['ItemLocationPostalUsa_model', 'Account Attributes'];
    }

    public function historyLabel(): string
    {
        return $this->street.', '.$this->city.' '.$this->zip;
    }
}
