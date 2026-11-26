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

    public function test_service_requests_reach_the_inbox_with_their_own_subject(): void
    {
        Mail::fake();

        foreach (['inkoop' => 'Auto verkopen', 'aankoop' => 'Aankoopbegeleiding'] as $type => $label) {
            $this->flushSession();
            $this->get(route('diensten', ['onderwerp' => $type]))
                ->assertOk()
                ->assertSee('<option value="' . $type . '" selected', false);

            $this->post(route('leads.store'), [
                'type' => $type,
                'name' => 'Test Klant',
                'email' => $type . '@example.com',
                'message' => 'Ik wil deze dienst bespreken.',
            ])->assertSessionHasNoErrors()->assertSessionHas('lead_sent', true);

            $lead = Lead::where('email', $type . '@example.com')->sole();
            $this->assertSame($type, $lead->type);
            $this->assertSame($label, $lead->typeLabel());
            Mail::assertQueued(LeadReceived::class, fn ($mail) => $mail->lead->is($lead));
        }
    }

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
        $mail->assertSeeInText('Hallo,');
        $mail->assertSeeInText('Proefrit aanvragen');
        $mail->assertSeeInText($car->title());
        $mail->assertSeeInText('donderdag 24 december 2026');
        $mail->assertSeeInText('om de afspraak te bevestigen');
        $mail->assertDontSeeInText('@if');
        $mail->assertHasReplyTo(config('brand.contact.email'));
    }

    /** Op de portfolio-demo komen aanvragen niet aan: dan ook niets opslaan of mailen. */
    public function test_demo_does_not_store_or_mail_personal_data(): void
    {
        Mail::fake();
        config(['brand.demo' => true]);

        $this->post(route('leads.store'), ['type' => 'vraag', 'name' => 'Jan', 'email' => 'jan@example.com', 'phone' => '0612345678'])
            ->assertRedirect()
            ->assertSessionHas('lead_demo', true)
            ->assertSessionMissing('lead_sent'); // geen "ontvangen" voor iets dat nergens aankomt

        $this->assertSame(0, Lead::count());
        Mail::assertNothingOutgoing();
    }

    /**
     * Zonder demobalk lijkt de demo de echte zaak. Wie een proefrit aanvraagt, moet vóór én na
     * het versturen weten dat het niet aankomt en hoe hij de dealer wél bereikt.
     */
    public function test_demo_form_says_up_front_and_afterwards_that_nothing_is_sent(): void
    {
        config(['brand.demo' => true]);
        $car = Car::factory()->create(['status' => 'available']);

        foreach ([route('cars.show', $car), route('contact'), route('diensten')] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('Aanvragen via dit formulier komen niet aan')
                ->assertSee('tel:' . config('brand.contact.phone_href'), false)
                ->assertSee('https://autobedrijfrijswijk.nl', false);
        }

        $this->from(route('contact'))->followingRedirects()
            ->post(route('leads.store'), ['type' => 'vraag', 'name' => 'Jan', 'email' => 'jan@example.com'])
            ->assertSee('Niet verstuurd')
            ->assertDontSee('we hebben je aanvraag ontvangen');
    }

    /** Op de echte site geen demotekst. */
    public function test_real_site_form_has_no_demo_note(): void
    {
        config(['brand.demo' => false]);

        $this->get(route('contact'))->assertOk()->assertDontSee('Aanvragen via dit formulier komen niet aan');
    }

    /** De bevestiging gaat naar een vrij in te vullen adres: dus geen vrij ingevulde tekst erin (spam/phishing-relay). */
    public function test_confirmation_does_not_relay_text_a_stranger_typed(): void
    {
        $lead = Lead::create([
            'type' => 'vraag', 'name' => 'Account geblokkeerd, ga naar evil.example',
            'email' => 'slachtoffer@example.com', 'message' => 'Klik op evil.example',
        ]);

        $mail = new LeadConfirmation($lead);
        $mail->assertDontSeeInText('evil.example');
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
