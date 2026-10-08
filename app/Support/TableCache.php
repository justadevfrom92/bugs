<?php

namespace App\Support;

use App\Models\TableSnapshot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Sheriff → Cached Tables. Saves a read-only copy of the table on each admin page that's
 * visited (when it changed since the last copy), and a copy of a database table whenever a
 * record is added to it. Secrets (passwords, tokens, keys, SSNs) are never copied.
 */
class TableCache
{
    private const SECRET = '/(password|token|secret|ssn|api_key|private_key)/i';

    /** After an admin page renders: keep a copy of its first table. */
    public static function page(string $path, string $html, ?string $app, ?int $userId): ?TableSnapshot
    {
        $main = Str::after($html, '<main');
        if (! preg_match('/<table\b.*?<\/table>/si', $main, $m)) {
            return null;
        }
        $table = self::staticCopy($m[0]);
        if ($table === null) {
            return null;
        }
        $title = preg_match('/<h1[^>]*>(.*?)<\/h1>/si', $main, $h) ? trim(html_entity_decode(strip_tags($h[1]))) : $path;

        return self::save(['kind' => 'page', 'key' => $path, 'title' => Str::limit($title, 180, ''), 'app' => $app, 'html' => $table,
            'row_count' => substr_count(strtolower(Str::after($table, '<tbody')), '<tr'), 'user_id' => $userId], $table);
    }

    /** When a record is created: keep a copy of its table's newest rows, the new one included. */
    public static function record(Model $model): ?TableSnapshot
    {
        $table = $model->getTable();
        if (in_array($table, ['table_snapshots', 'history_items', 'site_visits', 'sessions', 'cache', 'jobs'], true)) {
            return null;
        }
        $hidden = array_merge($model->getHidden(), config('history.hidden'));
        $columns = array_values(array_filter(Schema::getColumnListing($table), fn ($c) => ! in_array($c, $hidden, true) && ! preg_match(self::SECRET, $c)));
        $rows = $model->newQuery()->withoutGlobalScopes()->latest($model->getKeyName())->limit(25)->get()
            ->map(fn ($r) => collect($columns)->mapWithKeys(fn ($c) => [$c => self::cell($r->getAttributes()[$c] ?? null)])->all())->all();
        $label = Str::headline(class_basename($model));

        return self::save(['kind' => 'record', 'key' => $table, 'title' => $label.' added: '.Str::limit($model->historyLabel(), 120, '…'), 'columns' => $columns, 'rows' => $rows,
            'record_id' => $model->getKey(), 'row_count' => $model->newQuery()->withoutGlobalScopes()->count(), 'user_id' => auth('web')->id()], json_encode($rows));
    }

    private static function save(array $attributes, string $content): ?TableSnapshot
    {
        $hash = sha1($content);
        $last = TableSnapshot::where('kind', $attributes['kind'])->where('key', $attributes['key'])->latest('id')->first();
        if ($attributes['kind'] === 'page' && $last?->hash === $hash) {
            return null;   // unchanged since the last copy
        }
        $snap = TableSnapshot::create($attributes + ['hash' => $hash, 'created_at' => now()]);
        $old = TableSnapshot::where('kind', $attributes['kind'])->where('key', $attributes['key'])->orderByDesc('id')->skip(TableSnapshot::KEEP)->take(1000)->pluck('id');
        if ($old->isNotEmpty()) {
            TableSnapshot::whereIn('id', $old)->delete();
        }

        return $snap;
    }

    /** The table as plain read-only HTML: links become text; forms, buttons, inputs and scripts are removed. */
    private static function staticCopy(string $html): ?string
    {
        $doc = new \DOMDocument;
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div id="snap">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        $xp = new \DOMXPath($doc);
        foreach (iterator_to_array($xp->query('//script|//style|//form|//button|//input|//select|//textarea|//iframe|//template')) as $n) {
            $n->parentNode?->removeChild($n);
        }
        foreach (iterator_to_array($xp->query('//a')) as $a) {
            $span = $doc->createElement('span');
            while ($a->firstChild) {
                $span->appendChild($a->firstChild);
            }
            $a->parentNode->replaceChild($span, $a);
        }
        foreach (iterator_to_array($xp->query('//*[@*]')) as $el) {
            foreach (iterator_to_array($el->attributes) as $attr) {
                if (! in_array($attr->name, ['class', 'colspan', 'rowspan', 'title'], true)) {
                    $el->removeAttribute($attr->name);
                }
            }
        }
        $root = $doc->getElementById('snap') ?? $xp->query('//div')->item(0);
        $out = '';
        foreach ($root?->childNodes ?? [] as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim($out) === '' ? null : $out;
    }

    private static function cell(mixed $v): string
    {
        if ($v === null) {
            return '';
        }

        return Str::limit(is_scalar($v) ? (string) $v : json_encode($v), 120);
    }
}
