@extends('admin.layouts.app')

@section('content')
    @include('admin.partials.page-head', ['title' => 'Email Templates', 'sub' => 'Emails customers get. Corral\'s Send Email action and campaigns use these.',
        'actions' => '<a class="btn cyan" href="'.route('rodeo.templates.create').'">New Template</a>'])
    <div class="panel"><div class="table-wrap"><table>
        <thead><tr><th>Template</th><th>Subject</th><th class="num">Campaigns</th><th>Last Test</th><th>Updated</th><th></th></tr></thead>
        <tbody>@foreach ($templates as $t)<tr class="click" data-href="{{ route('rodeo.templates.edit', $t) }}"><td><a href="{{ route('rodeo.templates.edit', $t) }}"><b>{{ $t->name }}</b></a></td><td>{{ $t->subject }}</td><td class="num">{{ $t->campaigns_count }}</td><td>{{ $t->test_sends_max_created_at ? \Illuminate\Support\Carbon::parse($t->test_sends_max_created_at)->format('n/j/Y') : '—' }}@if ($t->test_sends_count)<span class="muted"> · {{ $t->test_sends_count }} {{ Str::plural('test', $t->test_sends_count) }}</span>@endif</td><td>{{ $t->updated_at->format('n/j/Y') }}</td><td class="actions"><a class="btn sm" href="{{ route('rodeo.templates.test', $t) }}">Send Test</a></td></tr>@endforeach</tbody>
    </table></div></div>
@endsection
