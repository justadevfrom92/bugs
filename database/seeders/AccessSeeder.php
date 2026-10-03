<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Roles and FICTIONAL demo users. Every demo user's password is ADMIN_DEMO_PASSWORD (default "password"). */
class AccessSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DatabaseSeeder::data('roles') as $r) {
            Role::updateOrCreate(['name' => $r['name']], ['perms' => $r['perms']]);
        }

        $password = env('ADMIN_DEMO_PASSWORD', 'password');
        foreach (DatabaseSeeder::data('users') as $u) {
            User::updateOrCreate(['email' => $u['email']], [
                'name' => $u['name'],
                'password' => $password,
                'role_id' => Role::where('name', $u['role'])->value('id'),
                'active' => $u['active'],
            ]);
        }
    }
}
