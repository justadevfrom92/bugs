<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A row in one of Sheriff's reference tables (table definitions live in config/admin.php). */
class ReferenceRow extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['cells' => 'array'];
    }
}
