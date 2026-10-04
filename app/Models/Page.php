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
        return ['no_index' => 'boolean', 'canonical' => 'boolean', 'hard_cache' => 'boolean'];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /** Content fields that "Copy From Page ID" copies or keeps in sync. */
    public const CONTENT_FIELDS = ['page_title', 'content_primary', 'content_secondary', 'content_parent', 'content_auxiliary', 'content_amp'];

    public function copyFrom(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'copy_from_id');
    }

    public function pricegridGroup(): BelongsTo
    {
        return $this->belongsTo(PlanGroup::class, 'pricegrid_group_id');
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    /** The page whose content is shown: the synced page, else this one. */
    public function contentSource(): Page
    {
        return $this->copy_from_id && $this->copyFrom ? $this->copyFrom : $this;
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
