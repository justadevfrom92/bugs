<?php

namespace App\Http\Controllers\Admin\Lando;

use App\Http\Controllers\Controller;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Lando → Sites: the websites this install serves, each answering on its own domain. */
class SiteController extends Controller
{
    public function index(): View
    {
        $sitemap = public_path('sitemap.xml');

        return view('admin.lando.sites.index', [
            'sites' => Site::withCount(['pages', 'blocks', 'pages as published_count' => fn ($q) => $q->where('status', 'Published')])->orderBy('id')->get(),
            'sitemapAt' => is_file($sitemap) ? Carbon::createFromTimestamp(filemtime($sitemap)) : null,
        ]);
    }

    public function create(): View
    {
        return view('admin.lando.sites.form', ['site' => new Site(['status' => 'Active'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $site = Site::create($this->validated($request));
        // Every site starts with a home page
        $site->pages()->create(['path' => '/', 'title' => 'Home', 'status' => 'Draft', 'content_primary' => '[[zip_form]]']);

        return redirect()->route('lando.pages.index', ['site' => $site->id])->with('status', 'Site added with a draft home page. Point '.$site->domain.' at this server to serve it.');
    }

    public function edit(Site $site): View
    {
        return view('admin.lando.sites.form', ['site' => $site]);
    }

    public function update(Request $request, Site $site): RedirectResponse
    {
        $site->update($this->validated($request, $site));

        return redirect()->route('lando.sites.index')->with('status', 'Site saved');
    }

    private function validated(Request $request, ?Site $site = null): array
    {
        $request->merge(['domain' => strtolower(trim((string) $request->input('domain')))]);

        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'domain' => ['required', 'string', 'max:190', 'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)*$/', Rule::unique('sites')->ignore($site)],
            'phone' => ['nullable', 'string', 'max:20'],
            'address_box_title' => ['nullable', 'string', 'max:120'],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
        ], ['domain.regex' => 'Enter just the host name, e.g. www.example.com (no https:// or slashes).']);
    }
}
