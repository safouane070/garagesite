<?php

namespace Tests\Feature;

use App\Support\Reviews;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReviewsTest extends TestCase
{
    use RefreshDatabase;

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

    /** Het aantal reviews komt uit de widget en verschijnt op de site, i.p.v. een vast getal. */
    public function test_fetch_stores_live_score_and_count_used_by_the_site(): void
    {
        Storage::fake('local');
        $this->assertSame(['rating' => (float) config('brand.reviews.rating'), 'count' => (int) config('brand.reviews.count')], Reviews::summary());

        \Illuminate\Support\Facades\Http::fake(['*' => '<div class="ti-review-item source-Google"><span data-rating="5.0"></span><div class="ti-name">Test Klant</div><div class="ti-review-content">Top.</div></div>'
            . '<div class="ti-rating-text"><span class="nowrap"><strong>Google</strong> waardering: </span><span class="nowrap"><strong>4.8</strong> van 5, </span><span class="nowrap">gebaseerd op <strong>231 recensies</strong></span></div>']);

        $this->artisan('reviews:fetch')->assertSuccessful();

        $this->assertSame(['rating' => 4.8, 'count' => 231], Reviews::summary());
        $this->get('/')->assertSee('231')->assertSee('4,8')->assertSee('"reviewCount":"231"', false);
    }
}
