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
        $password = app()->isLocal() ? 'password' : (env('ADMIN_PASSWORD') ?: Str::password(16, symbols: false));

        // Productie: standaard het echte zaak-adres, zodat "wachtwoord vergeten" werkt.
        $email = env('ADMIN_EMAIL') ?: (app()->isLocal() ? 'admin@autobedrijfrijswijk.test' : config('brand.contact.email'));

        // Alleen aanmaken, nooit overschrijven: de demo seedt bij elke herstart, en een
        // wachtwoord dat de beheerder zelf koos moet dan blijven staan.
        $admin = User::firstOrCreate(
            ['email' => $email],
            ['name' => 'Garage Beheerder', 'password' => Hash::make($password)],
        );
        if ($admin->wasRecentlyCreated && ! app()->isLocal() && ! env('ADMIN_PASSWORD')) {
            $this->command?->warn("Wachtwoord beheeraccount (bewaar dit!): {$password}");
        }

        // Voorraad: lokaal een demo-seed. In productie komt de voorraad uit
        // `php artisan cars:sync` (de echte, actuele voorraad van de dealersite).
        if (app()->isLocal()) {
            $this->call(CarSeeder::class);
            $this->call(CarEnrichmentSeeder::class);
        }
    }
}
