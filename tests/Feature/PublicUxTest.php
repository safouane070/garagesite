<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicUxTest extends TestCase
{
    use RefreshDatabase;

    /** Een auto met één foto (plus een titel met apostrof — komt voor in import-data). */
    private function carWithPhoto(array $attrs = []): Car
    {
        $car = Car::factory()->create(array_merge(['status' => 'available', 'variant' => "Men's Edition"], $attrs));
        $car->images()->create(['path' => "cars/{$car->slug}/0.jpg", 'is_primary' => true, 'sort_order' => 0]);

        return $car;
    }

    public function test_subject_can_be_preselected_via_link_and_garbage_is_ignored(): void
    {
        $this->get(route('contact', ['onderwerp' => 'financiering']))
            ->assertSee('<option value="financiering" selected', false);

        $this->get(route('contact', ['onderwerp' => '<script>']))
            ->assertSee('<option value="vraag" selected', false)
            ->assertDontSee('&lt;script&gt;', false); // niet teruggekaatst in de pagina
    }

    /** Home-teller en aanbodpagina tonen hetzelfde aantal (gereserveerd telt mee, verkocht niet). */
    public function test_stock_count_is_consistent_between_home_and_catalogue(): void
    {
        Car::factory()->count(2)->create(['status' => 'available']);
        Car::factory()->create(['status' => 'reserved']);
        Car::factory()->create(['status' => 'sold']);

        $this->get(route('home'))->assertSee('x-data="counter(3)"', false);
        $this->get(route('cars.index'))->assertSee('total: 3', false);
    }

    public function test_detail_page_renders_photo_server_side_and_escapes_title_in_js(): void
    {
        $car = $this->carWithPhoto();
        $html = $this->get(route('cars.show', $car))->assertOk()->getContent();

        // Eerste foto staat in de HTML (niet pas na JavaScript).
        $this->assertStringContainsString('src="' . $car->images->first()->url() . '"', $html);
        $this->assertStringContainsString('fetchpriority="high"', $html);

        // De apostrof in de titel breekt de JS-expressie niet meer.
        // Oude, kapotte vorm: HTML-escaping in een JS-string ("Men&#039;s" breekt de expressie).
        $this->assertStringNotContainsString("Men&#039;s Edition · foto '", $html);
        // Veilige vorm: JSON-ge-escapete apostrof binnen de JS-string van :alt.
        $this->assertMatchesRegularExpression('/:alt="\'[^"]*Men' . preg_quote(chr(92)) . 'u0027s Edition/', $html);
    }

    public function test_detail_page_offers_prefilled_whatsapp_and_keeps_paragraphs(): void
    {
        $car = $this->carWithPhoto(['description' => "Alinea één.\n\nAlinea twee."]);

        $this->get(route('cars.show', $car))
            ->assertSee('wa.me/' . config('brand.contact.whatsapp') . '?text=', false)
            ->assertSee('whitespace-pre-line', false)
            ->assertSee('href="#contact"', false);
    }

    /** Een fout in het formulier brengt je terug bij het formulier, niet bovenaan. */
    public function test_failed_lead_returns_to_the_form_anchor(): void
    {
        $car = $this->carWithPhoto();

        $this->from(route('cars.show', $car))
            ->post(route('leads.store'), ['type' => 'vraag', 'name' => '', 'email' => 'x'])
            ->assertRedirect(route('cars.show', $car) . '#contact');
    }

    /** Verwijder-bevestiging in het beheer blijft werken met een apostrof in de titel. */
    public function test_admin_delete_confirm_is_apostrophe_safe(): void
    {
        $this->carWithPhoto();

        $html = $this->actingAs(User::factory()->create())->get(route('admin.dashboard'))->getContent();

        $this->assertStringNotContainsString("Men&#039;s Edition” definitief", $html);
        $this->assertMatchesRegularExpression('/confirm\(\'[^)]*Men' . preg_quote(chr(92)) . 'u0027s Edition/', $html);
    }
}
