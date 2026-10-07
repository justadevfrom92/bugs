@php
    // Views in the last 7 days, visitors on the page now, admins editing it, blocked; and its Activity page
    $badges ??= function ($p) use ($traffic) {
        $pill = fn ($text, $tone) => ' '.view('admin.partials.pill', ['text' => $text, 'tone' => $tone])->render();
        $views = $traffic['views'][$p->id] ?? 0;
        $out = ' <span class="muted">· '.number_format($views).' '.Str::plural('view', $views).' this week</span>';
        if ($n = $traffic['online'][$p->id] ?? 0) $out .= $pill($n.' on it now', 'ok');
        if ($e = $traffic['editing'][$p->id] ?? null) $out .= $pill('Editing: '.collect($e)->pluck('name')->implode(', '), 'info');
        if ($traffic['blocked']->first(fn ($b) => $b->matchesPath($p->path))) $out .= $pill('Blocked', 'bad');
        if ($url = app_route('pages.activity', $p)) $out .= ' <a class="tree-act" href="'.$url.'">Activity</a>';

        return $out;
    };
@endphp
<ul class="tree">
    @foreach ($nodes as $segment => $node)
        @php
            $p = $node['page'];
            $label = $p
                ? '<a href="'.app_route('pages.edit', $p).'">'.e($segment).'</a> <span class="mono help">#'.$p->id.'</span>'
                  .($p->redirect ? ' <span class="redir">(redirect to '.e($p->redirect).')</span>' : '')
                  .' <span class="muted">· '.e($p->file ? 'built-in file '.$p->file : ($p->template?->name ?? 'no template')).'</span>'
                  .($p->components_count ? ' <span class="muted">· '.$p->components_count.' component'.($p->components_count > 1 ? 's' : '').'</span>' : '')
                  .($p->status !== 'Published' ? ' '.view('admin.partials.pill', ['text' => $p->status, 'tone' => 'warn'])->render() : '')
                  .(isset($traffic) ? $badges($p) : '')
                : e($segment);
        @endphp
        <li>
            @if ($node['children'])
                <details open><summary>{!! $label !!}</summary>@include('admin.lando.pages._tree', ['nodes' => $node['children'], 'badges' => $badges ?? null])</details>
            @else
                {!! $label !!}
            @endif
        </li>
    @endforeach
</ul>
