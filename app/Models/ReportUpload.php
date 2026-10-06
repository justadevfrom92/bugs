<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A report file someone uploaded to Walker for others to download. */
class ReportUpload extends Model
{
    use RecordsHistory;

    protected $guarded = ['id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function historyLabel(): string
    {
        return $this->title;
    }
}
