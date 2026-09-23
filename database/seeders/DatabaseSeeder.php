<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Beheeraccount. Lokaal het bekende demo-wachtwoord ("password", zie
        // README); in productie ADMIN_PASSWORD uit .env of een willekeurig
        // wachtwoord dat éénmalig in de console verschijnt.
        $password = 'password';
        if (! app()->isLocal()) {
            $password = env('ADMIN_PASSWORD') ?: Str::password(16, symbols: false);
            if (! env('ADMIN_PASSWORD')) {
                $this->command?->warn("Wachtwoord beheeraccount (bewaar dit!): {$password}");
            }
        }

        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@autobedrijfrijswijk.test')],
            ['name' => 'Garage Beheerder', 'password' => Hash::make($password)],
        );

        // Voorraad: lokaal een demo-seed. In productie komt de voorraad uit
        // `php artisan cars:sync` (de echte, actuele voorraad van de dealersite).
        if (app()->isLocal()) {
            $this->call(CarSeeder::class);
            $this->call(CarEnrichmentSeeder::class);
        }
    }
}
