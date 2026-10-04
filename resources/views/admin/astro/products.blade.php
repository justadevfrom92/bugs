@extends('admin.layouts.app')

@section('content')
    <form method="post" action="{{ route('astro.products.update') }}" style="display:flex;flex-direction:column;gap:20px">
        @csrf @method('put')
        @include('admin.partials.page-head', ['title' => 'BYOP Products', 'sub' => 'Add-ons offered in the website\'s Build Your Own Plan. Prices here are what the website shows.', 'actions' => '<button class="btn cyan">Save Products</button>'])
        <div class="panel"><div class="table-wrap"><table>
            <thead><tr><th>Product</th><th>Model</th><th>Step</th><th>Type</th><th class="num">Rate change ¢/kWh</th><th class="num">Monthly $</th><th class="num">Min Discount</th><th class="num">Max Discount</th><th>Show on Website</th></tr></thead>
            <tbody>@foreach ($products as $p)
                <tr><td><b>{{ $p->name }}</b></td><td class="mono">{{ $p->model }}</td><td>{{ $p->step }}</td><td>{{ $p->type }}</td>
                    <td class="num"><input type="number" step="0.01" name="p[{{ $p->id }}][rate_adj]" value="{{ number_format($p->rate_adj, 2, '.', '') }}" @class(['neg' => $p->rate_adj < 0]) data-track aria-label="{{ $p->name }} rate change"></td>
                    <td class="num"><input type="number" step="0.01" min="0" name="p[{{ $p->id }}][monthly]" value="{{ number_format($p->monthly, 2, '.', '') }}" data-track aria-label="{{ $p->name }} monthly price"></td>
                    @foreach (['min_discount' => 'min discount', 'max_discount' => 'max discount'] as $f => $l)
                        <td class="num"><input type="number" step="0.01" name="p[{{ $p->id }}][{{ $f }}]" value="{{ $p->$f !== null ? number_format($p->$f, 2, '.', '') : '' }}" data-track aria-label="{{ $p->name }} {{ $l }}" placeholder="—"></td>
                    @endforeach
                    <td><input type="hidden" name="p[{{ $p->id }}][active]" value="0">
                        <label class="check"><input type="checkbox" name="p[{{ $p->id }}][active]" value="1" @checked($p->active) data-track> <span data-label data-on="Shown" data-off="Hidden">{{ $p->active ? 'Shown' : 'Hidden' }}</span></label></td></tr>
            @endforeach</tbody>
        </table></div></div>
        @error('p')<div class="alert bad">{{ $message }}</div>@enderror
        <p class="help">A negative rate change is a discount. Min/Max Discount limit how far a promo or agent can discount the add-on (¢/kWh); leave blank for no limit. To change many at once use <a href="{{ route('astro.byop.upload') }}">Upload → BYOP Discounts</a>. Hidden products disappear from the website's Build Your Own Plan page.</p>
    </form>
@endsection
