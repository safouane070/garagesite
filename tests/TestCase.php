<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Sleep;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests wachten nooit echt (tempo naar de dealersite, backoff); wie het slapen wil
        // controleren, roept zelf Sleep::fake() aan en gebruikt de asserts.
        Sleep::fake(syncWithCarbon: true);
    }
}
