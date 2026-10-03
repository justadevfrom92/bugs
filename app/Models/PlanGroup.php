<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Decides which plans each website section shows (e.g. slug "featured" for the home page). */
class PlanGroup extends Model
{
    protected $guarded = ['id'];

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'plan_group_plan')->withPivot('position')->orderByPivot('position');
    }
}
