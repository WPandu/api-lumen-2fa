<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::truncate();

        $password = 'password';
        $roles = config('role');

        $admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@only.test',
            'password' => $password,
        ]);

        $admin->roles()->create([
            'role_id' => $roles[Role::ROLE_ADMIN],
        ]);

        $customer = User::create([
            'name' => 'Customer Test',
            'email' => 'customer@only.test',
            'password' => $password,
        ]);

        $customer->roles()->create([
            'role_id' => $roles[Role::ROLE_CUSTOMER],
        ]);

        $contributor = User::create([
            'name' => 'Contributor Test',
            'email' => 'contributor@only.test',
            'password' => $password,
        ]);

        $contributor->roles()->create([
            'role_id' => $roles[Role::ROLE_CONTRIBUTOR],
            'status' => UserRole::STATUS_APPROVED,
            'answered_by' => $admin->id,
            'answered_at' => now(),
        ]);
    }
}
