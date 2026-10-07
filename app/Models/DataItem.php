<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One original Corral item stored as its fields (see config/items.php and App\Support\Items). */
class DataItem extends Model
{
    use RecordsHistory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** History lists it under its original model name, in its parent log's group. */
    public function historyName(): array
    {
        $parent = config('items.models.'.$this->model.'.parents.0');

        return [$this->model, match ($parent) {
            'TicketProductLog_model' => 'Products', 'TicketErcotLog_model' => 'EDI Transactions', 'TicketAttributeLog_model' => 'Account Attributes',
            'TicketContentLog_model' => 'Content', 'TicketContactLog_model' => 'Emails', default => 'Account Attributes',
        }];
    }

    public function historyLabel(): string
    {
        return $this->summary ?: $this->model;
    }
}
