@extends('admin.layouts.app')

@section('content')
    @include('admin.partials.page-head', ['title' => 'Plans', 'sub' => 'Every plan the site can sell.', 'actions' => '<a class="btn" href="'.route('lando.plans.create').'">Add a Plan</a>'])
    @foreach (['Active Plans' => $active, 'Inactive Plans' => $inactive] as $heading => $list)
        <div class="panel"><div class="panel-head"><h2>{{ $heading }}</h2><span class="muted">{{ $list->count() }}</span></div>
            <div class="table-wrap"><table>
                <thead><tr><th>Type</th><th class="num">Term</th><th>Display Name</th><th>Internal Name</th><th>Rolloff</th><th>ETF</th><th class="num">MRC</th><th class="num">Green</th><th></th></tr></thead>
                <tbody>@foreach ($list as $p)
                    <tr><td>{{ $p->type }}</td><td class="num">{{ $p->term }}</td><td><b>{{ $p->name }}</b></td><td class="mono">{{ $p->internal }}</td><td class="mono">{{ $p->rolloff }}</td>
                        <td>{{ $p->etf }}</td><td class="num">{{ $p->mrc ? '$'.number_format($p->mrc, 2) : '-' }}</td><td class="num">{{ $p->green }}%</td>
                        <td><a class="btn sm ghost" href="{{ route('lando.plans.edit', $p) }}">Edit</a> @if ($p->active)<a class="btn sm ghost" href="{{ route('lando.rates.index', ['plan' => $p->internal]) }}">Rates</a>@endif</td></tr>
                @endforeach</tbody>
            </table></div>
        </div>
    @endforeach
@endsection
