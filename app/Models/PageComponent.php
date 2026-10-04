<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A content block placed in one zone of a page. */
class PageComponent extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(ContentBlock::class, 'content_block_id');
    }
}
