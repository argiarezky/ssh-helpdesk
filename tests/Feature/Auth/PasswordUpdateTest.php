<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->engineer()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    public function test_password_can_be_five_characters_long(): void
    {
        $user = User::factory()->engineer()->create();

        $this->actingAs($user)->put('/password', [
            'current_password' => 'password',
            'password' => 'abcde',
            'password_confirmation' => 'abcde',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('abcde', $user->refresh()->password));
    }

    public function test_password_cannot_be_longer_than_twelve_characters(): void
    {
        $user = User::factory()->engineer()->create();

        $this->actingAs($user)->put('/password', [
            'current_password' => 'password',
            'password' => 'thirteen_chars',
            'password_confirmation' => 'thirteen_chars',
        ])->assertSessionHasErrorsIn('updatePassword', 'password');
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $user = User::factory()->engineer()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('updatePassword', 'current_password')
            ->assertRedirect('/profile');
    }
}
