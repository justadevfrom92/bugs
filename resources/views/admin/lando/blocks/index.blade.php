@extends('admin.layouts.app')

@section('content')
    @include('admin.partials.page-head', ['title' => 'Content Blocks', 'sub' => 'Reusable HTML snippets placed into pages with shortcodes.', 'actions' => '<a class="btn" href="'.route('lando.blocks.create').'">Add a Block</a>'])
    <div class="panel"><div class="table-wrap"><table>
        <thead><tr><th class="num">ID</th><th>Name</th><th>Site</th><th>Updated</th><th>Shortcode</th><th></th></tr></thead>
        <tbody>@foreach ($blocks as $b)
            <tr><td class="num">{{ $b->id }}</td><td><b>{{ $b->name }}</b></td><td>{{ $b->site }}</td>
                <td>{{ $b->updated_at->toDateString() }}@if ($b->editor) <span class="muted">· {{ $b->editor->name }}</span>@endif</td>
                <td class="mono">[[block|id={{ $b->id }}]]</td><td><a class="btn sm ghost" href="{{ route('lando.blocks.edit', $b) }}">Edit</a></td></tr>
        @endforeach</tbody>
    </table></div></div>
@endsection
