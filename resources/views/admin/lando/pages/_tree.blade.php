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
                : e($segment);
        @endphp
        <li>
            @if ($node['children'])
                <details open><summary>{!! $label !!}</summary>@include('admin.lando.pages._tree', ['nodes' => $node['children']])</details>
            @else
                {!! $label !!}
            @endif
        </li>
    @endforeach
</ul>
