<?php

namespace Tests\Feature;

use App\Mail\LeadConfirmation;
use App\Mail\LeadReceived;
use App\Models\Car;
use App\Models\Lead;
use App\Models\LeadInboxSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LeadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['mail.default' => 'smtp']);
    }

    public function test_service_requests_reach_the_inbox_with_their_own_subject(): void
    {
        Mail::fake();

        foreach (['inkoop' => 'Auto verkopen', 'aankoop' => 'Aankoopbegeleiding'] as $type => $label) {
            $this->flushSession();
            $this->get(route('diensten', ['onderwerp' => $type]))
                ->assertOk()
                ->assertSee('<option value="'.$type.'" selected', false);

            $this->post(route('leads.store'), [
                'type' => $type,
                'name' => 'Test Klant',
                'email' => $type.'@example.com',
                'message' => 'Ik wil deze dienst bespreken.',
            ])->assertSessionHasNoErrors()->assertSessionHas('lead_sent', true);

            $lead = Lead::where('email', $type.'@example.com')->sole();
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

    public function test_render_requests_are_stored_without_email_or_customer_confirmation(): void
    {
        Mail::fake();
        config(['brand.demo' => true, 'mail.default' => 'log']);

        $this->from(route('contact'))->followingRedirects()
            ->post(route('leads.store'), ['type' => 'vraag', 'name' => 'Jan', 'email' => 'jan@example.com'])
            ->assertSee('we hebben je aanvraag ontvangen')
            ->assertDontSee('Niet verstuurd');

        $this->assertDatabaseHas('leads', ['email' => 'jan@example.com']);
        Mail::assertNothingOutgoing();
    }

    public function test_render_can_notify_the_configured_owner_without_contacting_the_real_dealer(): void
    {
        Mail::fake();
        config(['brand.demo' => true]);
        LeadInboxSettings::updateOrCreate(['id' => 1], ['notification_email' => 'eigenaar@example.com']);

        $this->post(route('leads.store'), ['type' => 'vraag', 'name' => 'Jan', 'email' => 'jan@example.com'])
            ->assertSessionHas('lead_sent', true);

        Mail::assertQueued(LeadReceived::class, fn ($mail) => $mail->hasTo('eigenaar@example.com'));
        Mail::assertNotQueued(LeadConfirmation::class);
        Mail::assertQueuedCount(1);
    }

    public function test_empty_notification_address_disables_email_without_disabling_the_inbox(): void
    {
        Mail::fake();
        LeadInboxSettings::updateOrCreate(['id' => 1], ['notification_email' => null]);

        $this->post(route('leads.store'), ['type' => 'vraag', 'name' => 'Jan', 'email' => 'jan@example.com'])
            ->assertSessionHas('lead_sent', true);

        $this->assertSame(1, Lead::count());
        Mail::assertNothingOutgoing();
    }

    public function test_form_has_no_obsolete_demo_notice(): void
    {
        config(['brand.demo' => true]);
        $car = Car::factory()->create(['status' => 'available']);

        foreach ([route('cars.show', $car), route('contact'), route('diensten')] as $url) {
            $this->get($url)->assertOk()->assertDontSee('Aanvragen via dit formulier komen niet aan');
        }
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
