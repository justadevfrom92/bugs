<?php

namespace App\Http\Controllers\Admin\Lando;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlanGroup;
use App\Support\History;
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
        $code = Plan::whereKey($data['plan_id'])->value('internal');
        History::record(['model' => 'PlanGroup_model', 'group' => 'Admin Changes', 'record_id' => $group->id, 'action' => 'updated',
            'summary' => $code.' added to '.$group->name, 'data' => ['group' => $group->slug, 'plans' => $group->plans()->pluck('internal')->implode(', ')]]);

        return back()->with('status', 'Plan added to '.$group->name);
    }

    public function detach(PlanGroup $group, Plan $plan): RedirectResponse
    {
        $group->plans()->detach($plan->id);
        History::record(['model' => 'PlanGroup_model', 'group' => 'Admin Changes', 'record_id' => $group->id, 'action' => 'updated',
            'summary' => $plan->internal.' removed from '.$group->name, 'data' => ['group' => $group->slug, 'plans' => $group->plans()->pluck('internal')->implode(', ')]]);

        return back()->with('status', $plan->internal.' removed from '.$group->name);
    }
}
