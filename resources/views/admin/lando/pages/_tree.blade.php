<ul class="tree">
    @foreach ($nodes as $segment => $node)
        @php
            $p = $node['page'];
            $label = $p
                ? '<a href="'.route('lando.pages.edit', $p).'">'.e($segment).'</a>'
                  .($p->redirect ? ' <span class="redir">(redirect to '.e($p->redirect).')</span>' : '')
                  .' <span class="muted">· '.e($p->template?->name ?? 'no template').'</span>'
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
