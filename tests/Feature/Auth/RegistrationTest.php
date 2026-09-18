<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class RegistrationTest extends TestCase
{
    /**
     * Publieke registratie is bewust uitgeschakeld (dit is een beheerderslogin;
     * accounts maak je via de seeder/tinker aan). Deze test bewaakt dat.
     */
    public function test_public_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound();
    }
}
