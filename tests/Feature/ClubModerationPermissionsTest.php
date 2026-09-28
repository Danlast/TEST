<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Comment;
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