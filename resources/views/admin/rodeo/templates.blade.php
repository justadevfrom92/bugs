@extends('admin.layouts.app')

@section('content')
    @include('admin.partials.page-head', ['title' => 'Email Templates', 'sub' => 'Emails customers get. Corral\'s Send Email action and campaigns use these.',
        'actions' => '<a class="btn cyan" href="'.route('rodeo.templates.create').'">New Template</a>'])
    <div class="panel"><div class="table-wrap"><table>
        <thead><tr><th>Template</th><th>Subject</th><th class="num">Campaigns</th><th>Updated</th></tr></thead>
        <tbody>@foreach ($templates as $t)<tr class="click" data-href="{{ route('rodeo.templates.edit', $t) }}"><td><a href="{{ route('rodeo.templates.edit', $t) }}"><b>{{ $t->name }}</b></a></td><td>{{ $t->subject }}</td><td class="num">{{ $t->campaigns_count }}</td><td>{{ $t->updated_at->format('n/j/Y') }}</td></tr>@endforeach</tbody>
    </table></div></div>
@endsection
