<?php

namespace App\Http\Controllers\Admin\Lando;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlanGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Groups decide which plans each website section shows, e.g. "featured" on the home page. */
class PlanGroupController extends Controller
{
    public function index(): View
    {
        return view('admin.lando.groups', [
            'groups' => PlanGroup::with('plans')->orderBy('id')->get(),
            'plans' => Plan::where('active', true)->orderBy('type')->orderBy('term')->get(),
        ]);
    }

    public function attach(Request $request, PlanGroup $group): RedirectResponse
    {
        $data = $request->validate(['plan_id' => ['required', 'exists:plans,id']]);
        $group->plans()->syncWithoutDetaching([$data['plan_id'] => ['position' => $group->plans()->count()]]);

        return back()->with('status', 'Plan added to '.$group->name);
    }

    public function detach(PlanGroup $group, Plan $plan): RedirectResponse
    {
        $group->plans()->detach($plan->id);

        return back()->with('status', $plan->internal.' removed from '.$group->name);
    }
}
