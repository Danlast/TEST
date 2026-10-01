<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Article;
use App\Models\ClubMembership;
use App\Models\Comment;
use App\Models\Event;
use App\Models\EventRegistration;
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

    public function test_club_ban_cleans_only_that_clubs_membership_registrations_and_comments(): void
    {
        $club = $this->createUser('club', UserRole::CLUB);
        $otherClub = $this->createUser('other-club', UserRole::CLUB);
        $target = $this->createUser('target', UserRole::USER);
        $replyAuthor = $this->createUser('reply-author', UserRole::USER);
        $clubEvent = $this->createEvent($club, 'Event in banned club');
        $otherEvent = $this->createEvent($otherClub, 'Event in other club');
        $article = Article::create([
            'user_id' => $club->id,
            'club_id' => $club->id,
            'title' => 'Club article',
            'content' => 'Article text',
        ]);

        ClubMembership::create(['user_id' => $target->id, 'club_id' => $club->id]);
        ClubMembership::create(['user_id' => $target->id, 'club_id' => $otherClub->id]);
        EventRegistration::create(['user_id' => $target->id, 'event_id' => $clubEvent->id]);
        EventRegistration::create(['user_id' => $target->id, 'event_id' => $otherEvent->id]);

        $profileComment = Comment::create([
            'user_id' => $target->id,
            'profile_user_id' => $club->id,
            'content' => 'Club profile comment',
        ]);
        $eventComment = Comment::create([
            'user_id' => $target->id,
            'event_id' => $clubEvent->id,
            'content' => 'Club event comment',
        ]);
        $eventReply = Comment::create([
            'user_id' => $replyAuthor->id,
            'event_id' => $clubEvent->id,
            'parent_id' => $eventComment->id,
            'reply_to_id' => $eventComment->id,
            'content' => 'Reply to preserved comment',
        ]);
        $articleComment = Comment::create([
            'user_id' => $target->id,
            'article_id' => $article->id,
            'content' => 'Club article comment',
        ]);
        $otherClubComment = Comment::create([
            'user_id' => $target->id,
            'event_id' => $otherEvent->id,
            'content' => 'Other club comment',
        ]);

        $this->actingAs($club)
            ->post(route('club.assignRole', $club), [
                'email' => $target->email,
                'action' => 'ban',
                'reason' => 'Нарушение правил клуба',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('club_memberships', [
            'user_id' => $target->id,
            'club_id' => $otherClub->id,
        ]);
        $this->assertDatabaseMissing('club_memberships', [
            'user_id' => $target->id,
            'club_id' => $club->id,
        ]);
        $this->assertDatabaseMissing('event_registrations', [
            'user_id' => $target->id,
            'event_id' => $clubEvent->id,
        ]);
        $this->assertDatabaseHas('event_registrations', [
            'user_id' => $target->id,
            'event_id' => $otherEvent->id,
        ]);
        $this->assertDatabaseMissing('comments', ['id' => $profileComment->id]);
        $this->assertDatabaseHas('comments', [
            'id' => $eventComment->id,
            'content' => Comment::DELETED_CONTENT,
        ]);
        $this->assertDatabaseHas('comments', ['id' => $eventReply->id]);
        $this->assertDatabaseMissing('comments', ['id' => $articleComment->id]);
        $this->assertDatabaseHas('comments', ['id' => $otherClubComment->id]);

        $this->assertTrue((bool) $target->fresh()->club_banned);

        $this->actingAs($target->fresh())
            ->get(route('club.profile', $club))
            ->assertForbidden()
            ->assertSee('Вы были забанены в данном клубе')
            ->assertSee('Причина:')
            ->assertSee('Нарушение правил клуба');

        $this->get(route('profile'))
            ->assertOk()
            ->assertDontSee($club->username)
            ->assertDontSee($clubEvent->title)
            ->assertSee($otherClub->username)
            ->assertSee($otherEvent->title);

        $this->get(route('event.show', $clubEvent))
            ->assertOk()
            ->assertSee('Вы были забанены в данном клубе')
            ->assertDontSee('Напишите комментарий')
            ->assertDontSee('Записаться')
            ->assertDontSee('Ваш ответ')
            ->assertDontSee('Вы забанены в этом клубе и не можете оставлять комментарии под его мероприятиями.');

        $this->post(route('comments.event.store', $clubEvent), ['content' => 'New comment'])
            ->assertRedirect()
            ->assertSessionHas('error', 'Вы были забанены в данном клубе.');

        $this->post(route('comments.reply', $eventReply), ['content' => 'New reply'])
            ->assertRedirect()
            ->assertSessionHas('error', 'Вы были забанены в данном клубе.');
    }

    private function createUser(string $name, UserRole $role): User
    {
        return User::create([
            'username' => $name . uniqid(),
            'email' => $name . uniqid() . '@example.test',
            'password' => 'password',
            'role' => $role,
        ]);
    }

    private function createEvent(User $club, string $title): Event
    {
        return Event::create([
            'title' => $title,
            'date' => now()->addDay()->toDateTimeString(),
            'place' => 'Казань',
            'latitude' => 55.7887,
            'longitude' => 49.1223,
            'description' => 'Event description',
            'min_entries' => 0,
            'max_entries' => 10,
            'club_id' => $club->id,
            'author_id' => $club->id,
        ]);
    }
}
