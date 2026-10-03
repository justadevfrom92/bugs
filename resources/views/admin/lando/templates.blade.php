@extends('admin.layouts.app')

@section('content')
    @include('admin.partials.page-head', ['title' => 'Page Templates', 'sub' => 'Layouts that pages are built on.'])
    <div class="panel"><div class="table-wrap"><table>
        <thead><tr><th>Template</th><th>Description</th><th class="num">Pages</th></tr></thead>
        <tbody>@foreach ($templates as $t)<tr><td class="mono"><b>{{ $t->name }}</b></td><td class="wrap">{{ $t->description }}</td><td class="num">{{ $t->pages_count }}</td></tr>@endforeach</tbody>
    </table></div></div>
@endsection
