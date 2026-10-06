<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    use RecordsHistory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'credit' => 'float', 'starts_on' => 'date', 'ends_on' => 'date'];
    }

    public function isLive(): bool
    {
        return $this->active && (! $this->starts_on || $this->starts_on->lte(today())) && (! $this->ends_on || $this->ends_on->gte(today()));
    }
}
