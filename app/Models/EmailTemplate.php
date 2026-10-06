<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\URL;

/** An email customers get (Rodeo → Email Templates). {{first_name}}, {{account}}, {{balance}}, {{plan}} and {{survey:slug}} are filled in. */
class EmailTemplate extends Model
{
    use RecordsHistory;

    protected $guarded = ['id'];

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function render(Customer $c): string
    {
        return preg_replace_callback('/\{\{\s*(first_name|account|balance|plan|survey:([a-z0-9-]+))\s*\}\}/', fn ($m) => match (true) {
            $m[1] === 'first_name' => e($c->first_name),
            $m[1] === 'account' => e($c->account),
            $m[1] === 'balance' => '$'.number_format($c->balance, 2),
            $m[1] === 'plan' => e($c->plan?->name ?? ''),
            // Signed, so a response is only tied to the account the email was sent to
            default => URL::signedRoute('survey.show', ['survey' => $m[2], 'c' => $c->account]),
        }, $this->body);
    }
}
