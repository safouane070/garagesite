<?php

namespace Tests\Feature;

use App\Support\Reviews;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReviewsTest extends TestCase
{
    public function test_falls_back_to_config_when_no_cache(): void
    {
        Storage::fake('local');

        $this->assertSame(config('brand.testimonials'), Reviews::all());
    }

    public function test_uses_cached_reviews_when_present(): void
    {
        Storage::fake('local');
        $cached = [['name' => 'Test Klant', 'rating' => 5, 'text' => 'Netjes geholpen.']];
        Storage::put(Reviews::CACHE, json_encode($cached));

        $this->assertSame($cached, Reviews::all());
    }
}
