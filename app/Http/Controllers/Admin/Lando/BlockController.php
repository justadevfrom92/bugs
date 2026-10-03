<?php

namespace App\Http\Controllers\Admin\Lando;

use App\Http\Controllers\Controller;
use App\Models\ContentBlock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Reusable HTML snippets placed into pages with [[block|id=N]]. */
class BlockController extends Controller
{
    public function index(): View
    {
        return view('admin.lando.blocks.index', ['blocks' => ContentBlock::with('editor')->orderBy('id')->get()]);
    }

    public function create(): View
    {
        return view('admin.lando.blocks.form', ['block' => new ContentBlock(['site' => config('brand.domain'), 'html' => ''])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $block = ContentBlock::create($this->validated($request) + ['site' => config('brand.domain'), 'updated_by' => $request->user()->id]);

        return redirect()->route('lando.blocks.edit', $block)->with('status', 'Block created');
    }

    public function edit(ContentBlock $block): View
    {
        return view('admin.lando.blocks.form', ['block' => $block]);
    }

    public function update(Request $request, ContentBlock $block): RedirectResponse
    {
        $block->update($this->validated($request) + ['updated_by' => $request->user()->id]);

        return redirect()->route('lando.blocks.edit', $block)->with('status', 'Block saved');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'html' => ['nullable', 'string', 'max:200000'],
        ]) + ['html' => ''];
    }
}
