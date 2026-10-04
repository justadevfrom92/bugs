@extends('admin.layouts.app')

@section('content')
    @include('admin.partials.page-head', ['title' => 'Content Blocks', 'sub' => 'HTML widgets. Add one to a page as a component (Edit Page → Page Components) or inside page content with its code.',
        'actions' => '<a class="btn" href="'.route('lando.blocks.create', array_filter(['category' => $category])).'">Add a Content Block</a>'])

    <div class="grid-2" style="grid-template-columns:260px 1fr;align-items:start">
        <div class="panel"><div class="panel-head"><h2>Categories</h2></div><div class="panel-body">
            <nav class="menu-list">
                <a href="{{ route('lando.blocks.index') }}" @class(['on' => $category === null])>All Content Blocks</a>
                @foreach ($categories as $cat)<a href="{{ route('lando.blocks.index', ['category' => $cat->id]) }}" @class(['on' => $category == $cat->id])>{{ $cat->name }} <span class="help">{{ $cat->blocks_count }}</span></a>@endforeach
                <a href="{{ route('lando.blocks.index', ['category' => 0]) }}" @class(['on' => $category === '0'])>Uncategorized <span class="help">{{ $uncategorized }}</span></a>
            </nav>
            <form method="post" action="{{ route('lando.blocks.categories.store') }}" class="form-row" style="margin-top:14px">@csrf
                <input name="name" required maxlength="60" placeholder="New category" aria-label="New category name" style="flex:1"><button class="btn sm">Add a Category</button>
            </form>
        </div></div>

        <div class="panel"><div class="table-wrap"><table>
            <thead><tr><th>Name</th><th>Code</th><th>Category</th><th>Site</th><th class="num">On Pages</th><th>Updated</th><th></th></tr></thead>
            <tbody>@forelse ($blocks as $b)
                <tr><td><b>{{ $b->name }}</b><br><span class="help">{{ \Illuminate\Support\Str::limit(strip_tags($b->html), 70) }}</span></td>
                    <td class="mono">{{ $b->shortcode() }}</td><td>{{ $b->category?->name ?? 'Uncategorized' }}</td><td>{{ $b->site?->name ?? 'All sites' }}</td>
                    <td class="num">{{ $b->components_count }}</td>
                    <td>{{ $b->updated_at->toDateString() }}@if ($b->editor) <span class="muted">· {{ $b->editor->name }}</span>@endif</td>
                    <td><a class="btn sm ghost" href="{{ route('lando.blocks.edit', $b) }}">Edit</a></td></tr>
            @empty <tr><td colspan="7" class="empty">No content blocks in this category.</td></tr> @endforelse</tbody>
        </table></div></div>
    </div>
@endsection
