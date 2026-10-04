<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\HistoryItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** Writes history_items rows, automatically from model events or by hand for other actions. */
class History
{
    /** Called by the RecordsHistory trait. */
    public static function forModel(Model $model, string $action): void
    {
        [$name, $group] = config('history.models.'.$model::class, [class_basename($model).'_model', 'Admin Changes']);
        $hidden = config('history.hidden');

        $changes = null;
        if ($action === 'updated') {
            $changes = [];
            foreach ($model->getChanges() as $field => $new) {
                if (in_array($field, config('history.ignore_changes'), true)) {
                    continue;
                }
                $changes[$field] = in_array($field, $hidden, true)
                    ? ['(hidden)', '(changed)']
                    : [self::scalar($model->getOriginal($field)), self::scalar($new)];
            }
            if (! $changes) {
                return; // only timestamps changed
            }
        }

        $label = $model->historyLabel();
        $summary = match ($action) {
            'created' => Str::headline(Str::before($name, '_model')).' created: '.$label,
            'deleted' => Str::headline(Str::before($name, '_model')).' deleted: '.$label,
            default => $label.' — '.collect($changes)->keys()->map(fn ($f) => str_replace('_', ' ', $f))->implode(', ').' changed',
        };

        self::record([
            'customer_id' => $model instanceof Customer ? $model->getKey() : $model->historyCustomerId(),
            'model' => $name,
            'group' => $group,
            'record_id' => $model->getKey(),
            'action' => $action,
            'summary' => Str::limit($summary, 250),
            'data' => collect($model->attributesToArray())->except($hidden)->map(fn ($v) => self::scalar($v))->all(),
            'changes' => $changes,
        ]);
    }

    /** Record any event. user_id and ip default to the current request. */
    public static function record(array $attributes): HistoryItem
    {
        return HistoryItem::create($attributes + [
            'user_id' => auth()->id(),
            'ip' => app()->runningInConsole() ? null : request()->ip(),
            'created_at' => now(),
        ]);
    }

    private static function scalar(mixed $v): mixed
    {
        if ($v instanceof \DateTimeInterface) {
            return $v->format('Y-m-d H:i:s');
        }

        return is_array($v) || is_object($v) ? json_encode($v) : $v;
    }
}
