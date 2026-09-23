<?php

namespace Tests\Feature;

use App\Mail\LeadReceived;
use App\Models\Car;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_can_submit_an_inquiry_for_a_car(): void
    {
        Mail::fake();
        $car = Car::factory()->create();

        $response = $this->post(route('leads.store'), [
            'car_id' => $car->id,
            'type' => 'bezichtiging',
            'name' => 'Jan Jansen',
            'email' => 'jan@example.com',
            'phone' => '0612345678',
            'message' => 'Graag een proefrit.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('lead_sent', true);

        $this->assertDatabaseHas('leads', [
            'car_id' => $car->id,
            'type' => 'bezichtiging',
            'email' => 'jan@example.com',
        ]);

        Mail::assertSent(LeadReceived::class);
    }

    public function test_honeypot_blocks_bots_and_stores_nothing(): void
    {
        Mail::fake();

        $this->post(route('leads.store'), [
            'type' => 'vraag',
            'name' => 'Spam Bot',
            'email' => 'bot@example.com',
            'website' => 'http://spam.example', // honeypot ingevuld
        ])->assertSessionHasErrors('website');

        $this->assertSame(0, Lead::count());
        Mail::assertNothingSent();
    }

    public function test_name_and_email_are_required_with_dutch_messages(): void
    {
        $this->post(route('leads.store'), ['type' => 'vraag'])
            ->assertSessionHasErrors([
                'name' => 'Naam is verplicht.',
                'email' => 'E-mailadres is verplicht.',
            ]);

        $this->assertSame(0, Lead::count());
    }

    /** Wisselt de bezoeker na het kiezen van een datum van onderwerp, dan geen datum opslaan. */
    public function test_preferred_date_is_dropped_for_non_appointment_types(): void
    {
        Mail::fake();

        $this->post(route('leads.store'), [
            'type' => 'vraag',
            'name' => 'Jan',
            'email' => 'jan@example.com',
            'preferred_date' => now()->addDays(2)->toDateString(),
        ])->assertRedirect();

        $this->assertNull(Lead::first()->preferred_date);
    }

    /** De dealer moet de gevraagde afspraakdatum in de mail zien. */
    public function test_mail_shows_the_preferred_date(): void
    {
        $lead = Lead::create([
            'type' => 'proefrit',
            'name' => 'Jan',
            'email' => 'jan@example.com',
            'preferred_date' => '2026-12-24',
        ]);

        (new LeadReceived($lead))->assertSeeInText('donderdag 24 december 2026');
    }
}
