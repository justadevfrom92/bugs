<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One run of a scheduled job, recorded by App\Console\Commands\TrackedCommand. */
class JobRun extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'finished_at' => 'datetime'];
    }
}
