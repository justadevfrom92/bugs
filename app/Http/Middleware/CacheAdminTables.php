<?php

namespace App\Http\Middleware;

use App\Support\AdminMenu;
use App\Support\TableCache;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Keeps a cached copy of the table on each admin page that's visited (Sheriff → Cached Tables). */
class CacheAdminTables
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if ($request->isMethod('GET') && $response->getStatusCode() === 200 && $request->user()
            && str_contains((string) $response->headers->get('Content-Type'), 'text/html')
            && ! $request->routeIs('sheriff.cache*')) {
            try {
                TableCache::page('/'.ltrim($request->getRequestUri(), '/'), (string) $response->getContent(), AdminMenu::currentApp(), $request->user()->id);
            } catch (\Throwable $e) {
                report($e);   // a failed copy never breaks the page
            }
        }

        return $response;
    }
}
