<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    use RecordsHistory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['no_index' => 'boolean', 'canonical' => 'boolean'];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(PageComponent::class)->orderBy('zone')->orderBy('position');
    }

    /** Path on its site. Built-in pages are served from their file. */
    public function url(): string
    {
        if ($this->file) {
            return $this->file === 'index.html' ? '/' : '/'.$this->file;
        }

        return $this->path === '/' ? '/' : '/'.ltrim($this->path, '/');
    }

    /** Full address, on the page's own site. */
    public function liveUrl(): string
    {
        $base = $this->site && $this->site->domain !== parse_url((string) config('app.url'), PHP_URL_HOST)
            ? request()->getScheme().'://'.$this->site->domain.(in_array(request()->getPort(), [80, 443]) ? '' : ':'.request()->getPort())
            : rtrim(url('/'), '/');

        return $base.$this->url();
    }
}
