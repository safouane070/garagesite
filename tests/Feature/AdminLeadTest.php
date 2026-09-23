<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLeadTest extends TestCase
{
    use RefreshDatabase;

    private function lead(array $attrs = []): Lead
    {
        return Lead::create(array_merge([
            'type' => 'proefrit',
            'name' => 'Jan Jansen',
            'email' => 'jan@example.com',
            'phone' => '06 12345678',
            'message' => "Regel één\nRegel twee",
        ], $attrs));
    }

    public function test_guests_cannot_see_the_inbox(): void
    {
        $this->get(route('admin.leads.index'))->assertRedirect(route('login'));
    }

    public function test_open_tab_shows_open_leads_with_car_and_date(): void
    {
        $car = Car::factory()->create(['status' => 'available']);
        $this->lead(['car_id' => $car->id, 'preferred_date' => '2026-12-24']);
        $handled = $this->lead(['name' => 'Al Geholpen']);
        $handled->handled_at = now();
        $handled->save();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.leads.index'))
            ->assertOk()
            ->assertSee('Jan Jansen')
            ->assertSee('Proefrit aanvragen')
            ->assertSee($car->shortTitle())
            ->assertSee('24 dec')
            ->assertSee('tel:0612345678', false)
            ->assertDontSee('Al Geholpen');
    }

    public function test_lead_can_be_marked_handled_and_reopened(): void
    {
        $lead = $this->lead();
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.leads.index'))
            ->patch(route('admin.leads.toggle', $lead))
            ->assertRedirect(route('admin.leads.index'))
            ->assertSessionHas('status');
        $this->assertNotNull($lead->refresh()->handled_at);

        $this->actingAs($admin)->get(route('admin.leads.index', ['tab' => 'afgehandeld']))->assertSee('Jan Jansen');

        $this->actingAs($admin)->patch(route('admin.leads.toggle', $lead));
        $this->assertNull($lead->refresh()->handled_at);
    }

    public function test_lead_can_be_deleted(): void
    {
        $lead = $this->lead();

        $this->actingAs(User::factory()->create())->delete(route('admin.leads.destroy', $lead));

        $this->assertDatabaseMissing('leads', ['id' => $lead->id]);
    }

    /** Het publieke formulier mag een aanvraag nooit als "afgehandeld" aanmaken. */
    public function test_public_form_cannot_set_handled_at(): void
    {
        $this->post(route('leads.store'), [
            'type' => 'vraag',
            'name' => 'Slimmerik',
            'email' => 'slim@example.com',
            'handled_at' => now()->toDateTimeString(),
        ]);

        $this->assertNull(Lead::where('email', 'slim@example.com')->first()->handled_at);
    }
}
