<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Site;
use App\Support\SiteContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Website pages built in Lando, and the components shown on the built-in pages. */
class SitePageController extends Controller
{
    public function show(Request $request, string $path)
    {
        $path = trim($path, '/');
        abort_if(in_array(Str::before($path, '/'), config('cms.reserved'), true), 404);

        $site = Site::forHost($request->getHost());
        $page = $site?->pages()->with(['template', 'site'])->where('path', $path)->first();
        // Drafts are visible to signed-in admin users only ("View Page Live" from Lando)
        abort_unless($page && ($page->status === 'Published' || $request->user()), 404);

        if ($page->redirect) {
            return redirect($page->redirect, 301);
        }
        if ($page->file) {
            return response()->file(public_path($page->file));
        }

        return response()->view('site.page', [
            'page' => $page,
            'site' => $site,
            'content' => [
                'primary' => SiteContent::expand($page->content_primary, $site),
                'secondary' => SiteContent::expand($page->content_secondary, $site),
                'parent' => SiteContent::expand($page->content_parent, $site),
                'auxiliary' => SiteContent::expand($page->content_auxiliary, $site),
            ],
            'zones' => SiteContent::zones($page),
        ]);
    }

    /** /site/components?page=plans.html — components for a built-in page's zones. */
    public function components(Request $request): JsonResponse
    {
        $file = basename((string) $request->query('page')) ?: 'index.html';
        $page = Site::forHost($request->getHost())?->pages()->with('site')->where('file', $file)->where('status', 'Published')->first();

        return response()->json($page ? SiteContent::zones($page) : (object) []);
    }
}
