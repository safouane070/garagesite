<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin-account voor de garage-eigenaar (zie README voor inloggegevens).
        User::updateOrCreate(
            ['email' => 'admin@kroon.test'],
            [
                'name' => 'Garage Beheerder',
                'password' => Hash::make('password'),
            ]
        );

        // Demo-auto's inclusief gegenereerde foto's.
        $this->call(CarSeeder::class);
    }
}
