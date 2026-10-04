<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Time an account spent in a queue. Open rows are its current queues. */
class QueueLog extends Model
{
    use RecordsHistory;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['entered_at' => 'datetime', 'exited_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** [model name, log group] for History. */
    public function historyName(): array
    {
        return ['TicketQueue_model', 'Queues'];
    }

    public function historyLabel(): string
    {
        return $this->queue;
    }
}
