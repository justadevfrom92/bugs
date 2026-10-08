<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

/** My Account logins for a few sample customers in different situations, for the test sign-ins (password "password"). */
class PortalLoginSeeder extends Seeder
{
    public function run(): void
    {
        $taken = Customer::whereNotNull('password')->pluck('id');
        foreach ([
            'test.business' => fn ($q) => $q->where('type', 'Small Business'),
            'test.pastdue' => fn ($q) => $q->where('balance', '>', 50)->where('status', 'Good - On Flow'),
            'test.credit' => fn ($q) => $q->where('balance', '<', 0),
            'test.deposit' => fn ($q) => $q->where('status', 'Pending - Deposit Due'),
            'test.moved' => fn ($q) => $q->whereIn('status', ['Dropped - Churned', 'Moved Out', 'Disconnected']),
        ] as $username => $where) {
            $c = $where(Customer::whereNotIn('id', $taken)->whereNotNull('email'))->orderBy('id')->first();
            if ($c) {
                $c->forceFill(['username' => $username, 'password' => 'password'])->saveQuietly();
                $taken->push($c->id);
            }
        }
    }
}
