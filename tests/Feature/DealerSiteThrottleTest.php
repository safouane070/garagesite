<?php

namespace Tests\Feature;

use App\Support\DealerSite;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

/**
 * De server van de dealer (nginx) laat ±1 aanvraag per 2 s toe en geeft daarboven
 * "429 Too Many Requests" zonder Retry-After. Zonder tempo las de sync 2% van de voorraad.
 */
class DealerSiteThrottleTest extends TestCase
{
    public function test_pages_and_api_are_paced(): void
    {
        Sleep::fake(syncWithCarbon: true);
        $this->travel(1)->minute(); // los van eerdere aanvragen in dit testproces
        Http::fake(['*' => Http::response('<html></html>')]);

        DealerSite::page('https://autobedrijfrijswijk.nl/voertuig/a/');
        DealerSite::page('https://autobedrijfrijswijk.nl/voertuig/b/');

        Sleep::assertSlept(fn ($d) => $d->totalMilliseconds >= 2000, 1);
    }

    public function test_429_is_retried_after_a_real_pause(): void
    {
        Sleep::fake();
        Http::fake(['*/wp-json/wp/v2/media/*' => Http::sequence()
            ->push('', 429)->push('', 429)
            ->push(['source_url' => 'https://autobedrijfrijswijk.nl/wp-content/uploads/2026/09/1-1.jpg'])]);

        $this->assertSame('https://autobedrijfrijswijk.nl/wp-content/uploads/2026/09/1-1.jpg', DealerSite::mediaUrl(7));
        Sleep::assertSlept(fn ($d) => $d->totalMilliseconds >= 10000, 2);
    }

    /** Een 404 komt niet terug door het nog eens te proberen: één aanvraag, geen extra belasting. */
    public function test_404_is_not_retried(): void
    {
        Http::fake(['*' => Http::response('', 404)]);

        $this->assertNull(DealerSite::page('https://autobedrijfrijswijk.nl/voertuig/weg/'));
        Http::assertSentCount(1);
    }

    /** Foto's (wp-content/uploads) vallen niet onder de limiet: die blijven op volle snelheid. */
    public function test_photo_downloads_are_not_paced(): void
    {
        Sleep::fake();
        Http::fake(['*' => Http::response('jpg')]);

        DealerSite::download('https://autobedrijfrijswijk.nl/wp-content/uploads/2026/09/1-1.jpg');
        DealerSite::download('https://autobedrijfrijswijk.nl/wp-content/uploads/2026/09/1-2.jpg');

        Sleep::assertNeverSlept();
    }
}
