@extends('admin.layouts.app')

@section('content')
    @include('admin.partials.page-head', ['title' => 'Markets', 'sub' => 'Texas utility service territories (TDSPs).'])
    <div class="panel"><div class="table-wrap"><table>
        <thead><tr><th class="num">ID</th><th>Name</th><th>Short</th><th>Description</th><th>Region</th><th>Utility Phone</th><th class="num">Zip Ranges</th></tr></thead>
        <tbody>@foreach ($markets as $m)
            <tr><td class="num">{{ $m->id }}</td><td class="mono"><b>{{ $m->name }}</b></td><td>{{ $m->short }}</td><td>{{ $m->description }}</td><td>{{ $m->region }}</td><td>{{ $m->phone }}</td><td class="num">{{ $m->zip_ranges_count }}</td></tr>
        @endforeach</tbody>
    </table></div></div>
@endsection
