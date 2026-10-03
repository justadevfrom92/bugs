<?php

namespace App\Http\Controllers\Admin\Lando;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Template;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PageController extends Controller
{
    /** Site structure as a tree built from page paths */
    public function index(): View
    {
        $tree = [];
        foreach (Page::with('template')->orderBy('path')->get() as $page) {
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

        return view('admin.lando.pages.index', ['tree' => $tree, 'count' => Page::count()]);
    }

    public function create(): View
    {
        return view('admin.lando.pages.form', ['page' => new Page(['status' => 'Draft']), 'templates' => Template::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Page::create($this->validated($request));

        return redirect()->route('lando.pages.index')->with('status', 'Page created');
    }

    public function edit(Page $page): View
    {
        return view('admin.lando.pages.form', ['page' => $page, 'templates' => Template::orderBy('name')->get()]);
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $page->update($this->validated($request, $page));

        return redirect()->route('lando.pages.index')->with('status', 'Page saved');
    }

    /** Needs the "delete" right. */
    public function destroy(Page $page): RedirectResponse
    {
        Gate::authorize('delete');
        $page->delete();

        return redirect()->route('lando.pages.index')->with('status', 'Page deleted');
    }

    public function templates(): View
    {
        return view('admin.lando.templates', ['templates' => Template::withCount('pages')->orderBy('name')->get()]);
    }

    public function sitemap(Request $request): RedirectResponse
    {
        Artisan::call('et:sitemap', ['--user' => $request->user()->id]);

        return back()->with('status', trim(Artisan::output()) ?: 'Sitemap rebuilt');
    }

    private function validated(Request $request, ?Page $page = null): array
    {
        $request->merge(['path' => trim((string) $request->input('path'), '/ ') ?: '/']);

        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'path' => ['required', 'string', 'max:200', 'regex:#^(/|[a-z0-9][a-z0-9/_-]*)$#', Rule::unique('pages')->ignore($page)],
            'template_id' => ['nullable', 'exists:templates,id'],
            'redirect' => ['nullable', 'string', 'max:300'],
            'status' => ['required', Rule::in(['Published', 'Draft'])],
            'meta_description' => ['nullable', 'string', 'max:300'],
        ], ['path.regex' => 'Use lowercase letters, numbers, dashes and slashes in the URL path.']);
    }
}
