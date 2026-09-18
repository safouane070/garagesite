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

    public function test_name_and_email_are_required(): void
    {
        $this->post(route('leads.store'), ['type' => 'vraag'])
            ->assertSessionHasErrors(['name', 'email']);

        $this->assertSame(0, Lead::count());
    }
}
