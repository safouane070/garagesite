<?php

namespace Tests\Feature;

use App\Mail\LeadConfirmation;
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

        // Beide mails via de wachtrij (niet tijdens het versturen van het formulier).
        Mail::assertQueued(LeadReceived::class, fn ($m) => $m->hasTo(config('brand.contact.email')));
        Mail::assertQueued(LeadConfirmation::class, fn ($m) => $m->hasTo('jan@example.com'));
        Mail::assertNothingSent();
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
        Mail::assertNothingOutgoing();
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

    /** De klant krijgt een bevestiging met wat hij aanvroeg; antwoorden gaan naar de zaak. */
    public function test_customer_confirmation_mentions_request_and_replies_go_to_the_dealer(): void
    {
        $car = Car::factory()->create(['status' => 'available']);
        $lead = Lead::create([
            'car_id' => $car->id, 'type' => 'proefrit', 'name' => "Els O'Brien",
            'email' => 'els@example.com', 'preferred_date' => '2026-12-24',
        ]);

        $mail = new LeadConfirmation($lead);
        $mail->assertSeeInText("Hallo Els O'Brien");
        $mail->assertSeeInText('Proefrit aanvragen');
        $mail->assertSeeInText($car->title());
        $mail->assertSeeInText('donderdag 24 december 2026');
        $mail->assertSeeInText('om de afspraak te bevestigen');
        $mail->assertDontSeeInText('@if');
        $mail->assertHasReplyTo(config('brand.contact.email'));
    }

    /** Hapert zelfs het inplannen, dan ziet de bezoeker tóch de bevestiging en staat de lead vast. */
    public function test_queue_failure_does_not_block_the_visitor(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('wachtrij onbereikbaar'));

        $this->post(route('leads.store'), ['type' => 'vraag', 'name' => 'Jan', 'email' => 'jan@example.com'])
            ->assertRedirect()
            ->assertSessionHas('lead_sent', true);

        $this->assertSame(1, Lead::count());
    }
}
