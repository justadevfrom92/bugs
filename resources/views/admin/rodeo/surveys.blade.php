@extends('admin.layouts.app')

@section('content')
    @include('admin.partials.page-head', ['title' => 'Surveys', 'sub' => 'Customer surveys answered on the website. Send one with a campaign: put {{survey:slug}} in an email template.',
        'actions' => '<a class="btn cyan" href="'.route('rodeo.surveys.create').'">New Survey</a>'])
    <div class="panel"><div class="table-wrap"><table>
        <thead><tr><th>Survey</th><th>Link</th><th class="num">Questions</th><th class="num">Responses</th><th>Status</th><th></th></tr></thead>
        <tbody>@forelse ($surveys as $s)
            <tr><td><a href="{{ route('rodeo.surveys.edit', $s) }}"><b>{{ $s->title }}</b></a></td><td class="mono help">/survey/{{ $s->slug }}</td><td class="num">{{ $s->questions_count }}</td><td class="num">{{ $s->responses_count }}</td>
                <td>@include('admin.partials.pill', ['text' => ucfirst($s->status), 'tone' => $s->status === 'active' ? 'ok' : ''])</td>
                <td><a class="btn sm" href="{{ route('rodeo.surveys.results', $s) }}">Results</a></td></tr>
        @empty <tr><td colspan="6" class="empty">No surveys yet.</td></tr> @endforelse</tbody>
    </table></div></div>
@endsection
