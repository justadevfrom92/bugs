@extends('admin.layouts.app')

@section('content')
    @include('admin.partials.page-head', ['title' => 'Uploaded Reports', 'sub' => 'Report files shared by the team: utility and ERCOT files, finance workbooks, vendor reports.'])

    <div class="grid-2" style="grid-template-columns:300px 1fr;align-items:start">
        <div style="display:flex;flex-direction:column;gap:20px">
            <form method="post" action="{{ route('walker.uploads.store') }}" enctype="multipart/form-data" class="panel">@csrf
                <div class="panel-head"><h2>Upload a Report</h2></div>
                <div class="panel-body" style="display:flex;flex-direction:column;gap:12px">
                    <div class="field"><label for="u-title">Title</label><input id="u-title" name="title" required value="{{ old('title') }}"></div>
                    <div class="field"><label for="u-cat">Category</label><select id="u-cat" name="category">@foreach (config('walker.upload_categories') as $c)<option @selected(old('category') === $c)>{{ $c }}</option>@endforeach</select></div>
                    <div class="field"><label for="u-desc">Description</label><textarea id="u-desc" name="description" style="min-height:70px">{{ old('description') }}</textarea></div>
                    <input type="file" name="file" required accept=".xlsx,.xls,.csv,.pdf,.txt,.zip" aria-label="File">
                    <p class="help">Excel, CSV, PDF, text or zip, up to 20 MB.</p>
                    <button class="btn cyan">Upload</button>
                </div>
            </form>
            <div class="panel"><div class="panel-head"><h2>Categories</h2></div><div class="panel-body"><nav class="menu-list">
                <a href="{{ route('walker.uploads.index') }}" @class(['on' => ! $category])>All</a>
                @foreach (config('walker.upload_categories') as $c)<a href="{{ route('walker.uploads.index', ['category' => $c]) }}" @class(['on' => $category === $c])>{{ $c }} <span class="help">{{ $counts[$c] ?? 0 }}</span></a>@endforeach
            </nav></div></div>
        </div>

        <div class="panel"><div class="table-wrap"><table>
            <thead><tr><th>Report</th><th>Category</th><th>File</th><th>Uploaded</th><th></th></tr></thead>
            <tbody>@forelse ($uploads as $u)
                <tr><td><b>{{ $u->title }}</b>@if ($u->description)<br><span class="help">{{ $u->description }}</span>@endif</td><td>{{ $u->category }}</td>
                    <td class="mono help">{{ $u->original_name }}<br>{{ number_format($u->size / 1024, 1) }} KB</td>
                    <td>{{ $u->created_at->format('n/j/Y') }}<br><span class="help">{{ $u->user?->name }}</span></td>
                    <td class="actions"><a class="btn sm ghost" href="{{ route('walker.uploads.download', $u) }}">Download</a>
                        @can('delete')<form method="post" action="{{ route('walker.uploads.destroy', $u) }}" class="inline" data-confirm="Delete {{ $u->title }}?|The file is removed for everyone.|Delete">@csrf @method('delete')<button class="btn sm ghost">Delete</button></form>@endcan</td></tr>
            @empty <tr><td colspan="5" class="empty">No uploaded reports{{ $category ? ' in '.$category : '' }}.</td></tr> @endforelse</tbody>
        </table></div>
        @include('admin.partials.pager', ['p' => $uploads])</div>
    </div>
@endsection
