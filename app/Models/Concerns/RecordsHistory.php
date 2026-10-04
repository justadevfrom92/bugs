<?php

namespace App\Models\Concerns;

use App\Support\History;
use Illuminate\Database\Eloquent\Model;

/**
 * Records every create, update and delete of the model in history_items,
 * with field-level changes. The model name and group come from config/history.php.
 */
trait RecordsHistory
{
    public static function bootRecordsHistory(): void
    {
        static::created(fn (Model $m) => History::forModel($m, 'created'));
        static::updated(fn (Model $m) => History::forModel($m, 'updated'));
        static::deleted(fn (Model $m) => History::forModel($m, 'deleted'));
    }

    /** The customer account this record belongs to, if any. */
    public function historyCustomerId(): ?int
    {
        return $this->getAttribute('customer_id');
    }

    /** One-line description for the history list. */
    public function historyLabel(): string
    {
        foreach (['name', 'title', 'reference', 'internal', 'summary', 'email'] as $field) {
            if (filled($this->getAttribute($field))) {
                return (string) $this->getAttribute($field);
            }
        }

        return '#'.$this->getKey();
    }
}
