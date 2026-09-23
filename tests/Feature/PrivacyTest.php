<?php

namespace Tests\Feature;

use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_google_fonts_anywhere_and_csp_no_longer_allows_them(): void
    {
        foreach (['home', 'contact', 'login'] as $route) {
            $response = $this->get(route($route));
            $response->assertDontSee('fonts.googleapis.com', false)->assertDontSee('fonts.gstatic.com', false);
            $this->assertStringNotContainsString('fonts.g', $response->headers->get('Content-Security-Policy'));
        }
    }

    /** Kaart en lease-widget staan alleen binnen <template> (inert) tot er geklikt wordt. */
    public function test_third_party_embeds_only_load_after_a_click(): void
    {
        foreach (['contact' => 'google.com/maps', 'financial-lease' => 'financiallease.nl/lease'] as $route => $src) {
            $html = $this->get(route($route))->assertOk()->getContent();

            $this->assertStringContainsString($src, $html);                          // wel aanwezig…
            $outsideTemplates = preg_replace('#<template.*?</template>#s', '', $html);
            $this->assertStringNotContainsString('<iframe', $outsideTemplates);     // …maar niet actief
            $this->assertStringContainsString('Daarbij deelt je browser gegevens', $html);
        }
    }

    public function test_old_leads_are_pruned_by_retention_policy(): void
    {
        $make = function (string $name, int $createdMonthsAgo, ?int $handledMonthsAgo) {
            $lead = Lead::create(['type' => 'vraag', 'name' => $name, 'email' => "{$name}@example.com"]);
            $lead->created_at = now()->subMonths($createdMonthsAgo);
            $lead->handled_at = $handledMonthsAgo === null ? null : now()->subMonths($handledMonthsAgo);
            $lead->save();
        };
        $make('oudafgehandeld', 14, 13);  // weg: > 12 mnd na afhandeling
        $make('recentafgehandeld', 12, 11); // blijft
        $make('stokoudopen', 25, null);   // weg: > 24 mnd oud
        $make('open', 23, null);          // blijft

        $this->artisan('model:prune', ['--model' => [Lead::class]])->assertSuccessful();

        $this->assertEqualsCanonicalizing(['recentafgehandeld', 'open'], Lead::pluck('name')->all());
    }

    public function test_privacy_page_states_the_retention_period(): void
    {
        $this->get(route('privacy'))->assertSee('12 maanden na afhandeling')->assertSee('nooit langer dan 24 maanden');
    }
}
