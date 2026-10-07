<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** An address no email goes to (Rodeo → Emails → Suppression List). */
class EmailSuppression extends Model
{
    use RecordsHistory;

    public const REASONS = ['bounce' => 'Hard bounce', 'unsubscribe' => 'Unsubscribed', 'complaint' => 'Spam complaint', 'manual' => 'Added by hand'];

    protected $guarded = ['id'];

    public static function has(?string $email): bool
    {
        return filled($email) && static::where('email', strtolower(trim($email)))->exists();
    }

    protected function setEmailAttribute(string $value): void
    {
        $this->attributes['email'] = strtolower(trim($value));
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
