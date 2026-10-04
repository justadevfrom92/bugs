<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role_id', 'active', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, RecordsHistory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function bookmarks(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'bookmarks')->withTimestamps();
    }

    /** True when the user's role grants an admin app (corral, lando, …) or an extra right (delete, refunds). */
    public function hasPerm(string $perm): bool
    {
        return $this->active && in_array($perm, $this->role?->perms ?? [], true);
    }

    /** The admin apps from config/admin.php this user can open, keyed by app key. */
    public function apps(): array
    {
        return array_filter(config('admin.apps'), fn ($app, $key) => $this->hasPerm($key), ARRAY_FILTER_USE_BOTH);
    }

    public function initials(): string
    {
        return collect(explode(' ', $this->name))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
    }
}
