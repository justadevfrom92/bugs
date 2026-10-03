<?php

namespace App\Http\Controllers\Admin\Lando;

use App\Http\Controllers\Controller;
use App\Models\Market;
use App\Models\TdspFee;
use App\Services\Catalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** TDSP delivery charges by market (kept as history, like rates) and the market list. */
class FeeController extends Controller
{
    public function index(Catalog $catalog): View
    {
        return view('admin.lando.fees', ['markets' => Market::orderBy('name')->get(), 'fees' => $catalog->currentFees()]);
    }

    public function update(Request $request, Catalog $catalog): RedirectResponse
    {
        $data = $request->validate([
            'fees' => ['required', 'array'],
            'fees.*.per_kwh' => ['required', 'numeric', 'min:0', 'max:100'],
            'fees.*.per_bill' => ['required', 'numeric', 'min:0', 'max:1000'],
            'fees.*.effective_on' => ['nullable', 'date'],
        ]);
        $current = $catalog->currentFees();
        $changed = 0;

        foreach ($data['fees'] as $marketId => $f) {
            $now = $current[$marketId] ?? null;
            if (! Market::whereKey($marketId)->exists()) {
                continue;
            }
            if ($now && abs($now->per_kwh - $f['per_kwh']) < 0.00005 && abs($now->per_bill - $f['per_bill']) < 0.005) {
                continue;
            }
            TdspFee::create(['market_id' => $marketId, 'per_kwh' => $f['per_kwh'], 'per_bill' => $f['per_bill'],
                'effective_on' => $f['effective_on'] ?? today(), 'created_by' => $request->user()->id]);
            $changed++;
        }

        return back()->with('status', $changed ? $changed.' TDSP fee changes saved' : 'No changes to save');
    }

    public function markets(): View
    {
        return view('admin.lando.markets', ['markets' => Market::withCount('zipRanges')->orderBy('id')->get()]);
    }
}
