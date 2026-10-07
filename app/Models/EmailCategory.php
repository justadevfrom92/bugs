<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A group email templates are filed under (Rodeo → Emails → Categories), e.g. Billing. */
class EmailCategory extends Model
{
    use RecordsHistory;

    protected $guarded = ['id'];

    public function templates(): HasMany
    {
        return $this->hasMany(EmailTemplate::class);
    }
}
