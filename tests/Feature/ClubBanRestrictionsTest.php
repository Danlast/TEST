<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClubBanRestrictionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_banned_from_a_club_cannot_participate_in_that_clubs_event(): void
    {
        $user = new User();
        $user->id = 11;
        $user->role = UserRole::USER;
        $user->club_banned = true;
        $user->club_ban_club_id = 10;

        $event = new Event();
        $event->club_id = 10;

        $this->assertFalse($user->canParticipateInClubEvent($event));
    }

    public function test_user_banned_by_other_club_can_still_participate(): void
    {
        $user = new User();
        $user->id = 11;
        $user->role = UserRole::USER;
        $user->club_banned = true;
        $user->club_ban_club_id = 20;

        $event = new Event();
        $event->club_id = 10;

        $this->assertTrue($user->canParticipateInClubEvent($event));
    }

    public function test_admin_can_open_a_club_page_even_if_club_ban_fields_are_set(): void
    {
        $club = User::create([
            'username' => 'Test club',
            'email' => 'club-page@example.test',
            'password' => 'password',
            'role' => UserRole::CLUB,
        ]);
        $admin = User::create([
            'username' => 'Test admin',
            'email' => 'admin-club-page@example.test',
            'password' => 'password',
            'role' => UserRole::ADMIN,
        ]);
        $admin->club_banned = true;
        $admin->club_ban_club_id = $club->id;
        $admin->save();

        $this->actingAs($admin)
            ->get(route('club.profile', $club))
            ->assertOk();
    }
}
