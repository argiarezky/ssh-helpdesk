<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class RegistrationTest extends TestCase
{
    public function test_registration_screen_is_unavailable(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_public_registration_is_unavailable(): void
    {
        $this->post('/register', [
            'name' => 'Example User',
            'email' => 'user@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();
    }
}
