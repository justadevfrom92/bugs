<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** A message sent from the website's Contact Us form. */
class ContactMessage extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['handled_at' => 'datetime'];
    }

    public function scopeOpen(Builder $query): void
    {
        $query->whereNull('handled_at');
    }
}
