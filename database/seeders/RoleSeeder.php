<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Role::truncate();

        $roles = config('role');
        $now = now();

        $data = [
            [
                'id' => $roles[Role::ROLE_ADMIN],
                'name' => Role::ROLE_ADMIN,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $roles[Role::ROLE_CUSTOMER],
                'name' => Role::ROLE_CUSTOMER,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $roles[Role::ROLE_CONTRIBUTOR],
                'name' => Role::ROLE_CONTRIBUTOR,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        Role::insert($data);
    }
}
