<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Comment;
use App\Models\ClubMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClubModerationPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_club_moderator_can_delete_comment_on_their_club_profile(): void
    {
        $club = $this->user('club', UserRole::CLUB);
        $moderator = $this->user('moderator', UserRole::CLUB_MODERATOR, $club->id);
        $author = $this->user('author', UserRole::USER);
        $comment = Comment::create([
            'user_id' => $author->id,
            'profile_user_id' => $club->id,
            'content' => 'Комментарий клуба',
        ]);

        $this->actingAs($moderator)
            ->delete(route('comments.destroy', $comment))
            ->assertRedirect();

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $moderator->id,
            'action' => 'comment.deleted',
        ]);
    }

    public function test_club_moderator_profile_shows_the_club_they_manage(): void
    {
        $club = $this->user('club', UserRole::CLUB);
        $moderator = $this->user('moderator', UserRole::CLUB_MODERATOR, $club->id);

        $this->actingAs($moderator)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('Модератор клуба')
            ->assertSee(route('club.profile', $club));

        $this->get(route('user.profile', $moderator))
            ->assertOk()
            ->assertSee('Модератор клуба')
            ->assertSee(route('club.profile', $club));

        $this->actingAs($club)
            ->get(route('club.profile', $club))
            ->assertOk()
            ->assertSee('Модераторы клуба')
            ->assertSee(route('user.profile', $moderator))
            ->assertSee($moderator->username);
    }

    public function test_only_the_club_account_can_edit_its_basic_profile_data(): void
    {
        $club = $this->user('club', UserRole::CLUB);
        $moderator = $this->user('moderator', UserRole::CLUB_MODERATOR, $club->id);

        $this->actingAs($moderator)
            ->get(route('club.edit', $club))
            ->assertForbidden();

        $this->post(route('club.update', $club), [
            'username' => 'Новое название',
            'email' => 'new-club@example.test',
            'description' => 'Обновлённое описание',
        ])->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $club->id,
            'username' => $club->username,
            'email' => $club->email,
        ]);
    }

    public function test_club_moderator_can_confirm_leaving_and_loses_the_role(): void
    {
        $club = $this->user('club', UserRole::CLUB);
        $moderator = $this->user('moderator', UserRole::CLUB_MODERATOR, $club->id);
        ClubMembership::create(['user_id' => $moderator->id, 'club_id' => $club->id]);

        $this->actingAs($moderator)
            ->get(route('club.profile', $club))
            ->assertOk()
            ->assertSee('club-action-menu', false)
            ->assertSee('Вы уверены, что хотите покинуть клуб и отказаться от роли модератора?');

        $this->post(route('club.leave', $club))
            ->assertRedirect(route('club.profile', $club));

        $this->assertDatabaseHas('users', [
            'id' => $moderator->id,
            'role' => UserRole::USER->value,
            'club_id' => null,
        ]);
        $this->assertDatabaseMissing('club_memberships', [
            'user_id' => $moderator->id,
            'club_id' => $club->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $moderator->id,
            'action' => 'club.moderator_left',
            'target_id' => $moderator->id,
        ]);
    }

    public function test_club_manager_can_ban_a_user_with_the_combined_action_form(): void
    {
        $club = $this->user('club', UserRole::CLUB);
        $target = $this->user('target', UserRole::USER);

        $this->actingAs($club)
            ->post(route('club.assignRole', $club), [
                'email' => $target->email,
                'action' => 'ban',
                'reason' => 'Нарушение правил клуба',
            ])
            ->assertRedirect();

        $target->refresh();
        $this->assertSame(1, (int) $target->club_banned);
        $this->assertSame($club->id, $target->club_ban_club_id);
        $this->assertSame('Нарушение правил клуба', $target->club_ban_reason);
    }

    public function test_club_manager_can_assign_a_moderator_with_the_combined_action_form(): void
    {
        $club = $this->user('club', UserRole::CLUB);
        $target = $this->user('target', UserRole::USER);

        $this->actingAs($club)
            ->post(route('club.assignRole', $club), [
                'email' => $target->email,
                'action' => UserRole::CLUB_MODERATOR->value,
            ])
            ->assertRedirect();

        $target->refresh();
        $this->assertSame(UserRole::CLUB_MODERATOR, $target->role);
        $this->assertSame($club->id, $target->club_id);
    }

    public function test_club_moderator_cannot_assign_or_remove_moderator_roles(): void
    {
        $club = $this->user('club', UserRole::CLUB);
        $moderator = $this->user('moderator', UserRole::CLUB_MODERATOR, $club->id);
        $target = $this->user('target', UserRole::USER);

        foreach ([UserRole::CLUB_MODERATOR->value, UserRole::USER->value] as $action) {
            $this->actingAs($moderator)
                ->post(route('club.assignRole', $club), [
                    'email' => $target->email,
                    'action' => $action,
                ])
                ->assertForbidden();
        }

        $this->assertSame(UserRole::USER, $target->fresh()->role);
        $this->assertNull($target->fresh()->club_id);
    }

    public function test_club_moderator_can_ban_a_regular_user_but_not_another_moderator(): void
    {
        $club = $this->user('club', UserRole::CLUB);
        $moderator = $this->user('moderator', UserRole::CLUB_MODERATOR, $club->id);
        $target = $this->user('target', UserRole::USER);
        $otherModerator = $this->user('other-moderator', UserRole::CLUB_MODERATOR, $club->id);

        $this->actingAs($moderator)
            ->post(route('club.assignRole', $club), [
                'email' => $target->email,
                'action' => 'ban',
                'reason' => 'Нарушение правил клуба',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'club_banned' => true,
            'club_ban_club_id' => $club->id,
        ]);

        $this->post(route('club.assignRole', $club), [
            'email' => $otherModerator->email,
            'action' => 'ban',
            'reason' => 'Нарушение правил клуба',
        ])->assertForbidden();

        $this->assertFalse((bool) $otherModerator->fresh()->club_banned);
    }

    public function test_ban_action_requires_a_reason(): void
    {
        $club = $this->user('club', UserRole::CLUB);
        $target = $this->user('target', UserRole::USER);

        $this->actingAs($club)
            ->from(route('club.profile', $club))
            ->post(route('club.assignRole', $club), [
                'email' => $target->email,
                'action' => 'ban',
            ])
            ->assertSessionHasErrors('reason');

        $this->assertSame(0, (int) $target->fresh()->club_banned);
    }

    private function user(string $name, UserRole $role, ?int $clubId = null): User
    {
        $user = User::create([
            'username' => $name . uniqid(),
            'email' => $name . uniqid() . '@example.test',
            'password' => 'password',
            'role' => $role,
        ]);

        $user->club_id = $clubId;
        $user->save();

        return $user;
    }
}