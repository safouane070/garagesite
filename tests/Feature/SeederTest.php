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

        $admin = User::firstWhere('email', config('brand.contact.email')); // echt adres: wachtwoord-reset werkt
        $this->assertNotNull($admin);
        $this->assertFalse(Hash::check('password', $admin->password));
        $this->assertSame(0, Car::count());
    }

    /** De demo-seeders wissen voorraad of zetten auto's op verkocht: op een server doen ze niets. */
    public function test_demo_seeders_refuse_to_touch_real_stock(): void
    {
        $this->app['env'] = 'production';
        $car = Car::factory()->create(['status' => 'available']);

        $this->artisan('db:seed', ['--class' => 'CarSeeder', '--force' => true]);
        $this->artisan('db:seed', ['--class' => 'CarEnrichmentSeeder', '--force' => true]);

        $this->assertModelExists($car);
        $this->assertSame('available', $car->fresh()->status->value);
    }

    /** De demo seedt bij elke herstart: een zelf gekozen wachtwoord mag daarbij niet verdwijnen. */
    public function test_reseeding_keeps_the_password_the_owner_chose(): void
    {
        $this->app['env'] = 'production';
        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();
        $admin = User::firstWhere('email', config('brand.contact.email'));
        $admin->update(['password' => 'eigen-gekozen-wachtwoord']);

        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

        $this->assertTrue(Hash::check('eigen-gekozen-wachtwoord', $admin->fresh()->password));
        $this->assertSame(1, User::count());
    }
}
