<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /** De homepage rendert ook met een lege database (alle secties zijn null-safe). */
    public function test_the_homepage_loads(): void
    {
        $this->get('/')->assertStatus(200);
    }
}
