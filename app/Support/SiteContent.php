<?php

namespace App\Support;

use App\Models\ContentBlock;
use App\Models\Page;
use App\Models\Site;

/**
 * Turns Lando content into website HTML: expands [[block|slug]] (or
 * [[block|id=N]]) codes and collects each zone's page components.
 * Content is written by signed-in Lando editors and is output as HTML.
 */
class SiteContent
{
    private const CODE = '/\[\[block\|(?:id=(\d+)|([A-Za-z0-9_-]+))\]\]/';

    public static function expand(?string $html, ?Site $site, int $depth = 0): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        $html = self::widgets($html, $site);

        $out = preg_replace_callback(self::CODE, function ($m) use ($site, $depth) {
            if ($depth >= 3) {
                return ''; // a block that includes itself
            }
            $block = ContentBlock::query()
                ->when($m[1] !== '', fn ($q) => $q->whereKey((int) $m[1]), fn ($q) => $q->where('slug', $m[2]))
                ->where(fn ($q) => $q->whereNull('site_id')->orWhere('site_id', $site?->id))
                ->first();

            return $block ? self::expand($block->html, $site, $depth + 1) : '';
        }, $html);

        // Codes this site doesn't support (e.g. old checkout codes) are left out rather than shown
        return $depth === 0 ? preg_replace('/\[\[[a-z_]+(\|[^\]]*)?\]\]/', '', $out) : $out;
    }

    /**
     * Built-in widgets editors can drop into content or blocks:
     * [[year]], [[phone]], [[zip_form]], [[plans|group=featured|limit=3]].
     * Anything else in [[…]] that isn't a block is left out of the page.
     */
    private static function widgets(string $html, ?Site $site): string
    {
        return preg_replace_callback('/\[\[(year|phone|zip_form|plans)((?:\|[a-z_]+=[A-Za-z0-9_-]+)*)\]\]/', function ($m) use ($site) {
            parse_str(str_replace('|', '&', ltrim($m[2], '|')), $opt);

            return match ($m[1]) {
                'year' => now()->format('Y'),
                'phone' => e($site?->phone ?: config('brand.phone')),
                'zip_form' => view('site.widgets.zip-form')->render(),
                'plans' => '<div class="plan-grid" id="plan-grid" data-group="'.e($opt['group'] ?? 'resi').'"'
                    .(isset($opt['limit']) ? ' data-limit="'.(int) $opt['limit'].'"' : '').'></div>',
            };
        }, $html);
    }

    /** @return array<string, string> zone => HTML of its components, in order */
    public static function zones(Page $page): array
    {
        $out = array_fill_keys(array_keys(config('cms.zones')), '');
        foreach ($page->components()->with('block')->get() as $c) {
            $b = $c->block;
            if ($b && ($b->site_id === null || $b->site_id === $page->site_id) && isset($out[$c->zone])) {
                $out[$c->zone] .= '<div class="cms-block" data-block="'.e($b->slug).'">'.self::expand($b->html, $page->site).'</div>';
            }
        }

        return $out;
    }
}
