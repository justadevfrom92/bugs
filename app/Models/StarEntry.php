<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Rewards stars earned (+) or spent (-). */
class StarEntry extends Model
{
    use RecordsHistory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['stars' => 'float'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** [model name, log group] for History. */
    public function historyName(): array
    {
        return ['ItemProductReward_model', 'Products'];
    }

    public function historyLabel(): string
    {
        return $this->reason.' '.($this->stars >= 0 ? '+' : '').$this->stars.' Stars';
    }
}
