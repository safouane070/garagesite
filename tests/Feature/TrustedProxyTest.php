<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\TrustProxies;
use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TrustProxies::flushState();
        parent::tearDown();
    }

    /** Proxy's komen uit de config (werkt dus ook na config:cache, waar env() niets meer geeft). */
    public function test_proxy_from_config_is_trusted(): void
    {
        $this->get('/', ['X-Forwarded-Proto' => 'https'])->assertHeaderMissing('Strict-Transport-Security');

        config(['app.trusted_proxies' => '*']);
        (new AppServiceProvider($this->app))->boot();

        $this->get('/', ['X-Forwarded-Proto' => 'https'])->assertHeader('Strict-Transport-Security');
    }
}
