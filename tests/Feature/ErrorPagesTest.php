<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    /** Oude link naar een verwijderde auto: Nederlandse 404 met weg naar het aanbod. */
    public function test_missing_car_shows_dutch_404_with_way_back(): void
    {
        $this->get('/aanbod/bestaat-niet-meer')
            ->assertNotFound()
            ->assertSee('Deze pagina bestaat niet (meer)')
            ->assertSee(route('cars.index'), false);
    }

    public function test_expired_session_shows_dutch_419(): void
    {
        Route::get('/_test/419', fn () => abort(419));

        $this->get('/_test/419')->assertStatus(419)->assertSee('Je sessie is verlopen');
    }

    /** Aanvraagformulier te vaak versturen: vriendelijke Nederlandse 429. */
    public function test_too_many_lead_submissions_show_dutch_429(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->post(route('leads.store'), ['type' => 'vraag', 'name' => 'X', 'email' => "x{$i}@example.com"]);
        }

        $this->post(route('leads.store'), ['type' => 'vraag', 'name' => 'X', 'email' => 'x7@example.com'])
            ->assertStatus(429)
            ->assertSee('Even rustig aan');
    }
}
