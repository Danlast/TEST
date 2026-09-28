<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserIdentityUniquenessTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_rejects_an_existing_username(): void
    {
        $this->createUser('taken-name', 'first@example.test');

        $this->post(route('send.reg'), [
            'username' => 'taken-name',
            'email' => 'second@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertSessionHasErrors('username')
            ->assertSessionHasErrors(['username' => 'Это имя пользователя уже занято.']);
    }

    public function test_registration_rejects_an_existing_email(): void
    {
        $this->createUser('first-name', 'taken@example.test');

        $this->post(route('send.reg'), [
            'username' => 'second-name',
            'email' => 'taken@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertSessionHasErrors('email')
            ->assertSessionHasErrors(['email' => 'Этот адрес электронной почты уже зарегистрирован.']);
    }

    public function test_profile_update_rejects_another_users_username_and_email(): void
    {
        $owner = $this->createUser('owner-name', 'owner@example.test');
        $other = $this->createUser('other-name', 'other@example.test');

        $this->actingAs($owner)
            ->post(route('profile.update'), [
                'username' => $other->username,
                'email' => $owner->email,
                'description' => '',
                'is_profile_private' => '0',
            ])
            ->assertSessionHasErrors(['username' => 'Это имя пользователя уже занято.']);

        $this->actingAs($owner)
            ->post(route('profile.update'), [
                'username' => $owner->username,
                'email' => $other->email,
                'description' => '',
                'is_profile_private' => '0',
            ])
            ->assertSessionHasErrors(['email' => 'Этот адрес электронной почты уже используется.']);
    }

    public function test_profile_update_allows_keeping_own_username_and_email(): void
    {
        $owner = $this->createUser('owner-name', 'owner@example.test');

        $this->actingAs($owner)
            ->post(route('profile.update'), [
                'username' => $owner->username,
                'email' => $owner->email,
                'description' => '',
                'is_profile_private' => '0',
            ])
            ->assertRedirect(route('profile'))
            ->assertSessionHasNoErrors();
    }

    private function createUser(string $username, string $email): User
    {
        return User::create([
            'username' => $username,
            'email' => $email,
            'password' => 'password',
        ]);
    }
}
