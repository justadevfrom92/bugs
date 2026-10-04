<?php

namespace App\Http\Controllers\Admin\Lando;

use App\Http\Controllers\Controller;
use App\Models\ContentBlock;
use App\Models\HistoryItem;
use App\Models\Market;
use App\Models\Page;
use App\Models\PageComponent;
use App\Models\Site;
use App\Models\Template;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Website pages (Lando → Pages, also Astro → Website → Pages). Each page belongs
 * to a site, has content areas and components, and is served at its path.
 */
class PageController extends Controller
{
    private function site(Request $request): Site
    {
        return Site::find($request->integer('site')) ?? Site::orderBy('id')->firstOrFail();
    }

    /** Site structure as a tree built from page paths */
    public function index(Request $request): View
    {
        $site = $this->site($request);
        $tree = [];
        foreach ($site->pages()->with('template')->withCount('components')->orderBy('path')->get() as $page) {
            $parts = $page->path === '/' ? ['/'] : explode('/', $page->path);
            $node = &$tree;
            foreach ($parts as $i => $part) {
                $node[$part] ??= ['page' => null, 'children' => []];
                if ($i === count($parts) - 1) {
                    $node[$part]['page'] = $page;
                }
                $node = &$node[$part]['children'];
            }
            unset($node);
        }

        return view('admin.lando.pages.index', ['site' => $site, 'sites' => Site::orderBy('name')->get(), 'tree' => $tree, 'count' => $site->pages()->count()]);
    }

    public function create(Request $request): View
    {
        return $this->form(new Page(['status' => 'Draft', 'site_id' => $this->site($request)->id, 'canonical' => true]));
    }

    public function store(Request $request): RedirectResponse
    {
        $page = Page::create($this->validated($request));

        return redirect(app_route('pages.edit', $page))->with('status', 'Page created. Add components below, then publish it.');
    }

    public function edit(Page $page): View
    {
        return $this->form($page);
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $page->update($this->validated($request, $page));

        return redirect(app_route('pages.edit', $page))->with('status', 'Page saved');
    }

    /** Needs the "delete" right. */
    public function destroy(Page $page): RedirectResponse
    {
        Gate::authorize('delete');
        abort_if($page->file !== null, 422, 'Built-in pages can\'t be deleted.');
        $site = $page->site_id;
        $page->delete();

        return redirect(app_route('pages.index', ['site' => $site]))->with('status', 'Page deleted');
    }

    // ---------- Page components ----------

    public function addComponent(Request $request, Page $page): RedirectResponse
    {
        $data = $request->validate([
            'content_block_id' => ['required', Rule::exists('content_blocks', 'id')->where(fn ($q) => $q->whereNull('site_id')->orWhere('site_id', $page->site_id))],
            'zone' => ['required', Rule::in(array_keys(config('cms.zones')))],
        ]);
        abort_if($page->file && ! in_array($data['zone'], ['top', 'bottom'], true), 422, 'Built-in pages only have top and bottom zones.');
        $data['position'] = (int) $page->components()->where('zone', $data['zone'])->max('position') + 1;
        $page->components()->create($data);

        return redirect(app_route('pages.edit', $page).'#components')->with('status', 'Component added');
    }

    public function moveComponent(Request $request, PageComponent $component): RedirectResponse
    {
        $dir = $request->validate(['dir' => ['required', Rule::in(['up', 'down'])]])['dir'];
        $siblings = PageComponent::where('page_id', $component->page_id)->where('zone', $component->zone)->orderBy('position')->orderBy('id')->get()->values();
        $i = $siblings->search(fn ($c) => $c->id === $component->id);
        $j = $dir === 'up' ? $i - 1 : $i + 1;
        if (isset($siblings[$j])) {
            $order = $siblings->all();
            [$order[$i], $order[$j]] = [$order[$j], $order[$i]];
            foreach ($order as $pos => $c) {
                $c->update(['position' => $pos]);
            }
        }

        return redirect(app_route('pages.edit', $component->page_id).'#components');
    }

    public function removeComponent(PageComponent $component): RedirectResponse
    {
        $page = $component->page_id;
        $component->delete();

        return redirect(app_route('pages.edit', $page).'#components')->with('status', 'Component removed');
    }

    public function sitemap(Request $request): RedirectResponse
    {
        Artisan::call('et:sitemap', ['--user' => $request->user()->id]);

        return back()->with('status', trim(Artisan::output()) ?: 'Sitemap rebuilt');
    }

    private function form(Page $page): View
    {
        $page->loadMissing('site');

        return view('admin.lando.pages.form', [
            'page' => $page,
            'sites' => Site::orderBy('name')->get(),
            'templates' => Template::orderBy('name')->get(),
            'markets' => Market::where('status', 'Active')->orderBy('name')->get(),
            'components' => $page->exists ? $page->components()->with('block.category')->get()->groupBy('zone') : collect(),
            'blocks' => ContentBlock::with('category')->where(fn ($q) => $q->whereNull('site_id')->orWhere('site_id', $page->site_id))->orderBy('name')->get(),
            'zones' => $page->file ? array_intersect_key(config('cms.zones'), array_flip(['top', 'bottom'])) : config('cms.zones'),
            'edits' => $page->exists
                ? HistoryItem::where('model', 'Page_model')->where('record_id', $page->id)->with('user')->latest('created_at')->latest('id')->limit(25)->get()
                : collect(),
        ]);
    }

    private function validated(Request $request, ?Page $page = null): array
    {
        $request->merge([
            'path' => trim((string) $request->input('path'), '/ ') ?: '/',
            'no_index' => $request->boolean('no_index'),
            'canonical' => $request->boolean('canonical'),
        ]);
        $siteId = $page?->site_id ?? $request->integer('site_id');

        $data = $request->validate([
            'site_id' => [$page ? 'prohibited' : 'required', 'exists:sites,id'],
            'title' => ['required', 'string', 'max:200'],
            'path' => ['required', 'string', 'max:200', 'regex:#^(/|[a-z0-9][a-z0-9/_-]*)$#',
                Rule::unique('pages')->where('site_id', $siteId)->ignore($page),
                function ($attr, $value, $fail) use ($page) {
                    if ($page?->file && $value !== $page->path) {
                        $fail('A built-in page keeps its path.');
                    } elseif (in_array(Str::before($value, '/'), config('cms.reserved'), true)) {
                        $fail('That path is used by the system. Pick another.');
                    }
                }],
            'template_id' => ['nullable', 'exists:templates,id'],
            'redirect' => ['nullable', 'string', 'max:300', 'regex:#^(/|https?://)#'],
            'status' => ['required', Rule::in(['Published', 'Draft'])],
            'html_title' => ['nullable', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'meta_keywords' => ['nullable', 'string', 'max:300'],
            'head_content' => ['nullable', 'string', 'max:20000'],
            'promo_code' => ['nullable', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]*$/'],
            'phone' => ['nullable', 'string', 'max:20'],
            'market_id' => ['nullable', 'exists:markets,id'],
            'no_index' => ['boolean'],
            'canonical' => ['boolean'],
            'page_title' => ['nullable', 'string', 'max:200'],
            'content_primary' => ['nullable', 'string', 'max:200000'],
            'content_secondary' => ['nullable', 'string', 'max:200000'],
            'content_parent' => ['nullable', 'string', 'max:50000'],
            'content_auxiliary' => ['nullable', 'string', 'max:200000'],
        ], [
            'path.regex' => 'Use lowercase letters, numbers, dashes and slashes in the URL path.',
            'redirect.regex' => 'Start the redirect with / or http(s)://.',
        ]);
        unset($data['site_id']);

        return $page ? $data : $data + ['site_id' => $siteId];
    }
}
