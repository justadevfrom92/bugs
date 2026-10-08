<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A cached copy of a table: an admin page's table when visited, or a database table when a record was added. */
class TableSnapshot extends Model
{
    public const UPDATED_AT = null;

    /** Copies kept per page or table; older ones are removed. */
    public const KEEP = 20;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['columns' => 'array', 'rows' => 'array', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
