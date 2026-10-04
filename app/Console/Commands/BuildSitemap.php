<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\Site;

/** Writes public/sitemap.xml from Lando's published pages (redirects are left out). */
class BuildSitemap extends TrackedCommand
{
    protected $signature = 'et:sitemap {--user= : ID of the admin who ran it}';

    protected $description = 'Rebuild sitemap.xml from published pages';

    protected function work(): array
    {
        $base = rtrim(config('app.url'), '/');
        // The main site (the one at APP_URL); other sites' pages are served on their own domains
        $site = Site::forHost(parse_url((string) config('app.url'), PHP_URL_HOST));
        $pages = Page::where('site_id', $site?->id)->where('status', 'Published')->where('no_index', false)->whereNull('redirect')->where('path', '!=', '404')->orderBy('path')->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($pages as $p) {
            $xml .= '  <url><loc>'.htmlspecialchars($base.$p->url(), ENT_XML1).'</loc><lastmod>'.$p->updated_at->toDateString().'</lastmod></url>'."\n";
        }
        $xml .= "</urlset>\n";
        file_put_contents(public_path('sitemap.xml'), $xml);

        return ['OK', 'Sitemap rebuilt ('.$pages->count().' URLs)'];
    }
}
