<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_can_update_their_comment(): void
    {
        $user = User::create([
            'username' => 'Автор',
            'email' => 'author@example.com',
            'password' => 'password123',
            'role' => 'user',
        ]);

        $club = User::create([
            'username' => 'Клуб',
            'email' => 'club@example.com',
            'password' => 'password123',
            'role' => 'club',
        ]);

        $event = Event::create([
            'title' => 'Тестовое мероприятие',
            'date' => now()->addDay()->toDateTimeString(),
            'place' => 'Казань',
            'description' => 'Описание',
            'min_entries' => 0,
            'max_entries' => 10,
            'image' => 'events/test.jpg',
            'latitude' => 55.7887,
            'longitude' => 49.1221,
            'tags' => ['discussion'],
            'club_id' => $club->id,
            'author_id' => $club->id,
        ]);

        $comment = Comment::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'content' => 'Старый текст',
        ]);

        $this->actingAs($user);

        $response = $this->put(route('comments.update', $comment), [
            'content' => 'Обновлённый текст',
        ]);

        $response->assertRedirect();
        $comment->refresh();
        $this->assertSame('Обновлённый текст', $comment->content);
    }

    public function test_user_can_reply_to_an_event_comment(): void
    {
        $user = User::create([
            'username' => 'Ответивший',
            'email' => 'reply@example.com',
            'password' => 'password123',
            'role' => 'user',
        ]);

        $event = Event::create([
            'title' => 'Открытое мероприятие',
            'date' => now()->addDay()->toDateTimeString(),
            'place' => 'Казань',
            'description' => 'Описание',
            'image' => '',
            'latitude' => 55.7887,
            'longitude' => 49.1221,
        ]);

        $parent = Comment::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'content' => 'Исходный комментарий',
        ]);

        $this->actingAs($user)
            ->post(route('comments.reply', $parent), ['content' => 'Ответ на комментарий'])
            ->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'parent_id' => $parent->id,
            'event_id' => $event->id,
            'content' => 'Ответ на комментарий',
        ]);
    }
}
