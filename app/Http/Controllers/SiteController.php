<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Services\Catalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** The few things the static website needs from Laravel. */
class SiteController extends Controller
{
    public function home()
    {
        return response()->file(public_path('index.html'));
    }

    /** /shared/config/brand.js — from config/brand.php */
    public function brand(): Response
    {
        return $this->script('ET.brand = '.json_encode(config('brand'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).';');
    }

    /** /shared/config/catalog.js — plans, rates, fees and pricing modifiers from the database */
    public function catalog(Catalog $catalog): Response
    {
        return $this->script('ET.defaults = '.json_encode($catalog->siteData(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).';');
    }

    /** CSRF token for the website's forms, and who (if anyone) is signed in to the admin. */
    public function session(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'csrf' => csrf_token(),
            'user' => $user?->active ? ['name' => $user->name] : null,
        ])->header('Cache-Control', 'no-store');
    }

    /** Contact Us form → Corral → Web Messages */
    public function contact(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first' => ['required', 'string', 'max:100'],
            'last' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:200'],
            'phone' => ['nullable', 'string', 'max:40'],
            'topic' => ['required', 'string', 'max:40'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        ContactMessage::create([
            'first_name' => $data['first'], 'last_name' => $data['last'], 'email' => $data['email'],
            'phone' => $data['phone'] ?? null, 'topic' => $data['topic'], 'message' => $data['message'],
            'ip' => $request->ip(),
        ]);

        return response()->json(['ok' => true], 201);
    }

    private function script(string $js): Response
    {
        return response("window.ET = window.ET || {};\n".$js."\n", 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'no-cache',
        ]);
    }
}
