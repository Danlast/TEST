<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfilePrivacyAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_a_public_profile(): void
    {
        $profile = $this->createUser();

        $this->get(route('user.profile', $profile))->assertOk();
    }

    public function test_private_profile_is_hidden_from_guests_and_other_users(): void
    {
        $profile = $this->createUser(['is_profile_private' => true]);
        $viewer = $this->createUser();

        $this->get(route('user.profile', $profile))
            ->assertNotFound()
            ->assertSee('Профиль скрыт')
            ->assertDontSee($profile->email);
        $this->actingAs($viewer)
            ->get(route('user.profile', $profile))
            ->assertNotFound()
            ->assertSee('Профиль скрыт')
            ->assertDontSee($profile->email);
    }

    public function test_owner_can_view_their_private_profile(): void
    {
        $profile = $this->createUser(['is_profile_private' => true]);

        $this->actingAs($profile)
            ->get(route('user.profile', $profile))
            ->assertOk();
    }

    public function test_other_users_cannot_comment_on_a_private_profile(): void
    {
        $profile = $this->createUser(['is_profile_private' => true]);
        $viewer = $this->createUser();

        $this->actingAs($viewer)
            ->post(route('comments.profile.store', $profile), ['content' => 'Private profile comment'])
            ->assertNotFound();

        $this->assertDatabaseMissing('comments', [
            'profile_user_id' => $profile->id,
            'content' => 'Private profile comment',
        ]);
    }

    public function test_user_can_save_private_profile_setting(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->post(route('profile.update'), [
                'username' => $user->username,
                'email' => $user->email,
                'description' => '',
                'is_profile_private' => '1',
            ])
            ->assertRedirect(route('profile'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_profile_private' => true,
        ]);
    }

    private function createUser(array $attributes = []): User
    {
        static $sequence = 0;
        $sequence++;

        return User::create(array_merge([
            'username' => 'Profile User ' . $sequence,
            'email' => 'profile' . $sequence . '@example.test',
            'password' => 'password',
        ], $attributes));
    }
}
