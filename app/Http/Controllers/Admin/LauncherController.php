<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The page the website's Admin button opens: one tile per app in config/admin.php. */
class LauncherController extends Controller
{
    public function __invoke(Request $request): View
    {
        $denied = config('admin.apps.'.$request->query('denied'));

        return view('admin.launcher', [
            'apps' => config('admin.apps'),
            'denied' => $denied ? $denied['name'] : null,
        ]);
    }
}
