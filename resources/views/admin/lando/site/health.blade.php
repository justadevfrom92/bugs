@extends('admin.layouts.app')

@section('content')
    @php $by = collect($checks)->countBy('state'); @endphp
    @include('admin.partials.page-head', ['title' => 'Site Health',
        'sub' => 'Checked just now: '.($by['ok'] ?? 0).' OK, '.($by['warn'] ?? 0).' to look at, '.($by['bad'] ?? 0).' '.Str::plural('problem', $by['bad'] ?? 0).'.',
        'actions' => '<a class="btn ghost" href="'.route('lando.health').'">Check Again</a>'])
    <div class="it-grid">
        <div class="panel">@include('admin.lando.site._checks', ['checks' => $checks])</div>
        <div class="it-side"><div class="panel"><div class="panel-head"><h2>About This Server</h2></div>
            <div class="table-wrap"><table><tbody>@foreach ($about as $k => $v)<tr><td>{{ $k }}</td><td class="mono">{{ $v }}</td></tr>@endforeach</tbody></table></div>
        </div></div>
    </div>
@endsection
