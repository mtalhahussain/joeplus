<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'admin',
            'company',
            'guest',
        ];

        foreach ($roles as $role) {
            Role::create(['name' => $role, 'guard_name' => 'sanctum']);
        }
    }
}
