<?php

namespace App\Http\Controllers\Admin\Sheriff;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.sheriff.users', [
            'users' => User::with('role')->orderByDesc('active')->orderBy('name')->get(),
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:200', 'unique:users,email'],
            'role_id' => ['required', 'exists:roles,id'],
            'password' => ['required', Password::min(12)],
        ]);
        User::create($data + ['active' => true]);

        return back()->with('status', $data['name'].' added. Share the password with them securely.');
    }

    /** Change role, enable/disable, or set a new password. */
    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role_id' => ['sometimes', 'exists:roles,id'],
            'active' => ['sometimes', 'boolean'],
            'password' => ['sometimes', 'nullable', Password::min(12)],
        ]);

        if ($user->is($request->user()) && (($data['active'] ?? true) == false || (isset($data['role_id']) && $data['role_id'] != $user->role_id))) {
            return back()->withErrors(['user' => 'You cannot disable your own account or change your own role.']);
        }
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $user->update($data);

        return back()->with('status', $user->name.' updated');
    }
}
