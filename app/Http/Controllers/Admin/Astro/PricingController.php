<?php

namespace App\Http\Controllers\Admin\Astro;

use App\Http\Controllers\Controller;
use App\Models\ByopProduct;
use App\Models\Market;
use App\Models\TermDiscount;
use App\Models\TermEtf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Term discounts, ETFs and Build Your Own Plan products — all read by the website's plan builder. */
class PricingController extends Controller
{
    private function regions(): array
    {
        return array_merge(['Generic'], Market::orderBy('id')->pluck('region')->unique()->values()->all());
    }

    public function terms(): View
    {
        return view('admin.astro.terms', [
            'regions' => $this->regions(),
            'grid' => TermDiscount::all()->groupBy('term')->map(fn ($rows) => $rows->pluck('discount', 'region'))->sortKeys(),
        ]);
    }

    public function updateTerms(Request $request): RedirectResponse
    {
        $data = $request->validate(['d' => ['required', 'array'], 'd.*' => ['array'], 'd.*.*' => ['required', 'numeric', 'min:-20', 'max:20']]);
        $regions = $this->regions();
        DB::transaction(function () use ($data, $regions) {
            foreach ($data['d'] as $term => $byRegion) {
                foreach ($byRegion as $region => $value) {
                    if ((int) $term >= 1 && (int) $term <= 36 && in_array($region, $regions, true)) {
                        TermDiscount::updateOrCreate(['term' => (int) $term, 'region' => $region], ['discount' => $value]);
                    }
                }
            }
        });

        return back()->with('status', 'Term modifiers saved');
    }

    public function etfs(): View
    {
        return view('admin.astro.etfs', ['etfs' => TermEtf::orderBy('term')->get()]);
    }

    public function updateEtfs(Request $request): RedirectResponse
    {
        $data = $request->validate(['etf' => ['required', 'array'], 'etf.*' => ['required', 'integer', 'min:0', 'max:5000']]);
        foreach ($data['etf'] as $term => $amount) {
            if ((int) $term >= 1 && (int) $term <= 36) {
                TermEtf::updateOrCreate(['term' => (int) $term], ['amount' => $amount]);
            }
        }

        return back()->with('status', 'ETFs saved');
    }

    public function products(): View
    {
        return view('admin.astro.products', ['products' => ByopProduct::orderBy('position')->get()]);
    }

    public function updateProducts(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'p' => ['required', 'array'],
            'p.*.rate_adj' => ['required', 'numeric', 'min:-10', 'max:10'],
            'p.*.monthly' => ['required', 'numeric', 'min:0', 'max:500'],
            'p.*.active' => ['required', 'boolean'],
        ]);
        foreach ($data['p'] as $id => $values) {
            ByopProduct::whereKey($id)->update($values);
        }

        return back()->with('status', 'Products saved. The website uses these prices now.');
    }
}
