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