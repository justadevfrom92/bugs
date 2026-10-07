<?php

namespace App\Support;

use App\Models\Page;
use App\Models\SiteBlock;
use App\Models\SiteVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/** Website page views and blocking for Lando → Site. */
class SiteTraffic
{
    public const COOKIE = 'et_vid';

    /** "/plans.html" → ["plans"-page path, page id]. Built-in files map to their Lando page; "/" and "/index.html" are the home page. */
    public static function normalize(string $path): array
    {
        $path = trim(Str::before(Str::before($path, '?'), '#'), '/');
        $path = preg_replace('#^(index\.php/)#', '', $path);
        if ($path === '' || $path === 'index.html') {
            $path = '/';
        }
        $page = str_ends_with($path, '.html')
            ? Page::where('file', $path)->first(['id', 'path'])
            : Page::where('path', $path)->first(['id', 'path']);

        return [Str::limit($page?->path ?? $path, 250, ''), $page?->id];
    }

    /** The block in force for this IP or path, if any. Cached briefly: it's checked on every website request. */
    public static function blockFor(string $ip, string $path): ?SiteBlock
    {
        // Plain arrays in the cache: it doesn't unserialize objects
        $blocks = SiteBlock::hydrate(Cache::remember('site-blocks', 30, fn () => SiteBlock::inForce()->get()->map->getAttributes()->all()));

        return $blocks->first(fn (SiteBlock $b) => $b->type === 'ip' && $b->matchesIp($ip))
            ?? $blocks->first(fn (SiteBlock $b) => $b->type === 'area' && $path !== '/' && $b->matchesPath($path));
    }

    public static function forget(): void
    {
        Cache::forget('site-blocks');
    }

    public static function hit(SiteBlock $block): void
    {
        SiteBlock::whereKey($block->id)->increment('hits', 1, ['last_hit_at' => now()]);   // the cached copy's count is stale
    }

    /** Records a page view and returns it (and the block, when the visitor is kept out). */
    public static function record(Request $request, string $rawPath, ?string $referrer): array
    {
        [$path, $pageId] = self::normalize($rawPath);
        $block = self::blockFor($request->ip(), $path);
        if ($block) {
            self::hit($block);
        }
        $visitor = $request->cookie(self::COOKIE);
        $visitor = is_string($visitor) && preg_match('/^[a-zA-Z0-9]{20,40}$/', $visitor) ? $visitor : Str::random(32);
        $ref = $referrer && ! str_starts_with($referrer, $request->getSchemeAndHttpHost()) ? Str::limit($referrer, 250, '') : null;
        $visit = SiteVisit::create(['visitor' => $visitor, 'ip' => $request->ip(), 'path' => $path, 'page_id' => $pageId,
            'customer_id' => auth('customer')->id(), 'referrer' => $ref, 'agent' => Str::limit((string) $request->userAgent(), 250, ''),
            'blocked' => (bool) $block, 'created_at' => now(), 'seen_at' => now()]);

        return [$visit, $block];
    }

    /** Who has a page's edit screen open in Lando (opened in the last 10 minutes). */
    public static function editing(?int $pageId = null, ?array $mark = null): array
    {
        $cutoff = now()->subMinutes(10)->getTimestamp();
        $all = collect(Cache::get('page-editing', []))->map(fn ($users) => array_filter($users, fn ($e) => $e['at'] > $cutoff))->filter();
        if ($pageId && $mark) {
            $all[$pageId] = [$mark['id'] => ['name' => $mark['name'], 'at' => now()->getTimestamp()]] + ($all[$pageId] ?? []);
            Cache::put('page-editing', $all->all(), 3600);
        }

        // page id => [user id => [name, at]], or one page's list
        return $pageId ? ($all[$pageId] ?? []) : $all->all();
    }
}
