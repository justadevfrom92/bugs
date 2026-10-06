<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Survey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * A customer survey on the website. Campaign emails link here with a signed
 * ?c=<account> so the answer is tied to that customer; a signed-in My Account
 * customer is used otherwise, and anyone else answers anonymously.
 */
class SurveyController extends Controller
{
    public function show(Request $request, Survey $survey): View
    {
        $survey->load('questions');

        return view('site.survey', ['survey' => $survey, 'customer' => $this->customer($request),
            'query' => $request->hasValidSignature() ? $request->query() : []]);
    }

    public function store(Request $request, Survey $survey): RedirectResponse
    {
        abort_unless($survey->status === 'active', 410);
        $rules = [];
        foreach ($survey->questions->values() as $i => $q) {
            $rules["a.$i"] = match ($q->type) {
                'rating' => ['nullable', 'integer', 'between:0,10'],
                'choice' => ['nullable', Rule::in($q->options ?? [])],
                default => ['nullable', 'string', 'max:2000'],
            };
        }
        $answers = $request->validate($rules)['a'] ?? [];
        $survey->responses()->create([
            'customer_id' => $this->customer($request)?->id,
            'answers' => array_map(fn ($i) => $answers[$i] ?? null, array_keys($survey->questions->all())),
            'created_at' => now(),
        ]);

        return redirect()->route('survey.show', $survey)->with('thanks', true);
    }

    /** The customer this answer belongs to: from the signed email link, else the My Account login. */
    private function customer(Request $request): ?Customer
    {
        if ($request->hasValidSignature() && $request->query('c')) {
            return Customer::where('account', $request->query('c'))->first();
        }

        return auth('customer')->user();
    }
}
