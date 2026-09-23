<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    /** Productie: geen demo-voorraad en géén "password" als beheer-wachtwoord. */
    public function test_production_seed_creates_only_admin_with_a_strong_password(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('db:seed', ['--force' => true])->assertSuccessful(); // zoals in DEPLOY.md

        $admin = User::firstWhere('email', 'admin@autobedrijfrijswijk.test');
        $this->assertNotNull($admin);
        $this->assertFalse(Hash::check('password', $admin->password));
        $this->assertSame(0, Car::count());
    }
}
