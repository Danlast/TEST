<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventRegistrationModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_manager_can_remove_another_users_registration(): void
    {
        $club = $this->createUser('club', 'club');
        $moderator = $this->createUser('moderator', 'club_moderator', $club->id);
        $attendee = $this->createUser('attendee', 'user');
        $event = Event::create([
            'title' => 'Книжная встреча',
            'date' => now()->addDay()->toDateTimeString(),
            'place' => 'Казань',
            'latitude' => 55.7887,
            'longitude' => 49.1221,
            'description' => 'Встреча клуба',
            'min_entries' => 0,
            'max_entries' => 20,
            'club_id' => $club->id,
            'author_id' => $club->id,
        ]);
        $registration = EventRegistration::create([
            'user_id' => $attendee->id,
            'event_id' => $event->id,
        ]);

        $this->actingAs($moderator)
            ->post(route('favorites.destroy', $event), [
                'user_id' => $attendee->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Запись пользователя удалена.');

        $this->assertDatabaseMissing('event_registrations', ['id' => $registration->id]);
    }

    public function test_non_manager_cannot_remove_another_users_registration(): void
    {
        $club = $this->createUser('club', 'club');
        $attendee = $this->createUser('attendee', 'user');
        $otherUser = $this->createUser('other', 'user');
        $event = Event::create([
            'title' => 'Книжная встреча',
            'date' => now()->addDay()->toDateTimeString(),
            'place' => 'Казань',
            'latitude' => 55.7887,
            'longitude' => 49.1221,
            'description' => 'Встреча клуба',
            'min_entries' => 0,
            'max_entries' => 20,
            'club_id' => $club->id,
            'author_id' => $club->id,
        ]);
        $registration = EventRegistration::create([
            'user_id' => $attendee->id,
            'event_id' => $event->id,
        ]);

        $this->actingAs($otherUser)
            ->post(route('favorites.destroy', $event), [
                'user_id' => $attendee->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('event_registrations', ['id' => $registration->id]);
    }

    private function createUser(string $name, string $role, ?int $clubId = null): User
    {
        $user = User::create([
            'username' => $name . uniqid(),
            'email' => $name . uniqid() . '@example.test',
            'password' => 'password',
            'role' => $role,
        ]);

        if ($clubId) {
            $user->club_id = $clubId;
            $user->save();
        }

        return $user;
    }
}