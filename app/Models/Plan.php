<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use RecordsHistory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['tags' => 'array', 'perks' => 'array', 'active' => 'boolean', 'mrc' => 'float', 'term' => 'integer', 'green' => 'integer'];
    }

    public function rates(): HasMany
    {
        return $this->hasMany(Rate::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(PlanGroup::class, 'plan_group_plan')->withPivot('position');
    }

    /** First dollar amount in the ETF text ("$300/$50x" → 300), or 0. */
    public function etfAmount(): int
    {
        return preg_match('/\d+/', (string) $this->etf, $m) ? (int) $m[0] : 0;
    }
}
