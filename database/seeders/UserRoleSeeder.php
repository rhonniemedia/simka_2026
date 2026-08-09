<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['id' => (string) Str::uuid(), 'name' => 'superadmin', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['id' => (string) Str::uuid(), 'name' => 'admin', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['id' => (string) Str::uuid(), 'name' => 'user', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('user_roles')->insert($roles);
    }
}
