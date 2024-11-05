<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
       $this->call(RolerSeeder::class);

        $admin = \App\Models\User::updateOrCreate(['email' => 'admin@admin.com'],[
            'name' => 'Super Admin',
            'password' => Hash::make('password'),
        ]);

        $admin->assignRole('admin');

        $guest = \App\Models\User::updateOrCreate(['email' => 'guest@gmail.com'],[
            'name' => 'Guest User',
            'password' => Hash::make('password'),
        ]);

        $guest->assignRole('guest');

        $company = \App\Models\User::updateOrCreate(['email' => 'company@techno.com'],[
            'name' => 'Company User',
            'password' => Hash::make('password'),
        ]);

        $company->assignRole('company');
    }
}
