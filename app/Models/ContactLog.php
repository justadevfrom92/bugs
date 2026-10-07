<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** An email or text sent to the customer. */
class ContactLog extends Model
{
    use RecordsHistory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime', 'dropped_at' => 'datetime', 'opened_at' => 'datetime', 'clicked_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** [model name, log group] for History. */
    public function historyName(): array
    {
        return [$this->channel === 'SMS' ? 'ItemSms_model' : 'ItemEmailSalesforce_model', 'Emails'];
    }

    public function historyLabel(): string
    {
        return $this->channel.': '.$this->template;
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /** Where the email got to: dropped, clicked, opened, sent, queued or not sent. */
    public function stage(): string
    {
        return match (true) {
            (bool) $this->dropped_at => 'dropped',
            (bool) $this->clicked_at => 'clicked',
            (bool) $this->opened_at => 'opened',
            (bool) $this->sent_at => 'sent',
            default => $this->status,
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
