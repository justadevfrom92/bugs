<?php

namespace App\Support;

/**
 * Reads uploaded .env text and writes chosen KEY=value pairs into the app's .env file
 * (config admin.env_path). Values are never returned to the browser.
 */
class EnvFile
{
    /** KEY=value pairs from .env text: comments, blank lines and "export " are allowed; quotes are removed. */
    public static function parse(string $text): array
    {
        $out = [];
        foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (! preg_match('/^(?:export\s+)?([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)$/', $line, $m)) {
                continue;
            }
            $value = trim($m[2]);
            if (preg_match('/^"(.*)"$/s', $value, $q)) {
                $value = stripcslashes($q[1]);
            } elseif (preg_match("/^'(.*)'$/s", $value, $q)) {
                $value = $q[1];
            } else {
                $value = trim(preg_replace('/\s+#.*$/', '', $value));   // inline comment
            }
            $out[strtoupper($m[1])] = $value;
        }

        return $out;
    }

    /** Sets each key in the .env file, replacing its line or adding it at the end. */
    public static function write(array $pairs): void
    {
        $path = config('admin.env_path');
        $text = is_file($path) ? (string) file_get_contents($path) : '';
        foreach ($pairs as $key => $value) {
            $line = $key.'='.self::quote((string) $value);
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';
            $text = preg_match($pattern, $text)
                ? preg_replace_callback($pattern, fn () => $line, $text, 1)
                : rtrim($text, "\n").($text === '' ? '' : "\n").$line."\n";
        }
        file_put_contents($path, $text, LOCK_EX);
    }

    private static function quote(string $value): string
    {
        $value = str_replace(["\r", "\n"], '', $value);

        return $value === '' || preg_match('/^[A-Za-z0-9_.:\/@+\-,=]*$/', $value) ? $value : '"'.addcslashes($value, '"\\$').'"';
    }
}
