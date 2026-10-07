@extends('admin.layouts.app')

@section('content')
    @include('admin.partials.page-head', ['title' => 'Survey Categories',
        'sub' => 'Topics that questions are filed under. Results and responses are grouped by these, in this order.'])

    <div class="sv-builder">
        <div class="panel" style="min-width:0"><div class="table-wrap"><table class="table">
            <thead><tr><th class="num">Order</th><th>Category</th><th>Description</th><th class="num">In the Bank</th><th class="num">In Surveys</th><th></th></tr></thead>
            <tbody>@forelse ($categories as $c)
                <tr><td class="num">{{ $c->position }}</td>
                    <td><span class="sv-cat"><span class="sv-dot" style="--dot:{{ $c->color }}"></span>{{ $c->name }}</span></td>
                    <td class="wrap">{{ $c->description }}</td>
                    <td class="num"><a href="{{ route('rodeo.surveys.bank', ['category' => $c->id]) }}">{{ $c->bank_questions_count }}</a></td>
                    <td class="num">{{ $c->questions_count }}</td>
                    <td class="actions"><a class="btn sm ghost" href="{{ route('rodeo.surveys.categories', ['edit' => $c->id]) }}">Edit</a>
                        <form method="post" action="{{ route('rodeo.surveys.categories.destroy', $c) }}" class="inline" data-confirm="Delete {{ $c->name }}?|Its {{ $c->bank_questions_count + $c->questions_count }} questions become uncategorized; nothing else changes.|Delete">@csrf @method('delete')<button class="btn sm ghost">Delete</button></form></td></tr>
            @empty <tr><td colspan="6" class="empty">No categories yet.</td></tr> @endforelse</tbody>
        </table></div></div>

        <aside class="sv-side">
            <form method="post" action="{{ $edit ? route('rodeo.surveys.categories.update', $edit) : route('rodeo.surveys.categories.store') }}" class="panel">@csrf @if ($edit) @method('put') @endif
                <div class="panel-head"><h3 style="margin:0">{{ $edit ? 'Edit Category' : 'New Category' }}</h3>@if ($edit)<a class="help" href="{{ route('rodeo.surveys.categories') }}">Cancel</a>@endif</div>
                <div class="panel-body sv-qbody" style="grid-template-columns:1fr">
                    <label>Name<input name="name" required maxlength="60" value="{{ old('name', $edit?->name) }}"></label>
                    <label>Description<input name="description" maxlength="200" value="{{ old('description', $edit?->description) }}"></label>
                    <label>Color<input type="color" name="color" value="{{ old('color', $edit?->color ?? '#00AEEF') }}" style="height:38px;padding:2px"></label>
                    <label>Order<input type="number" name="position" min="0" max="999" value="{{ old('position', $edit?->position) }}" placeholder="Last"></label>
                    <button class="btn cyan">{{ $edit ? 'Save Category' : 'Add Category' }}</button>
                </div>
            </form>
        </aside>
    </div>
@endsection
