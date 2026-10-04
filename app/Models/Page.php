<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Page extends Model
{
    use RecordsHistory;

    protected $guarded = ['id'];

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function url(): string
    {
        return $this->path === '/' ? '/' : '/'.ltrim($this->path, '/');
    }
}
