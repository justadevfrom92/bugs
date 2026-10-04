<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A role's perms list holds admin app keys (corral, lando, …) plus extra rights (delete, refunds). */
class Role extends Model
{
    use RecordsHistory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['perms' => 'array'];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function isAdministrator(): bool
    {
        return $this->name === 'Administrator';
    }
}
