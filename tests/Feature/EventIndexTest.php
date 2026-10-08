<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_index_can_filter_and_sort_events_from_database(): void
    {
        $club = User::create([
            'username' => 'Клуб',
            'email' => 'club@example.com',
            'password' => 'password',
            'role' => 'club',
        ]);

        $event = Event::create([
            'title' => 'Книжная встреча',
            'date' => now()->addDay()->toDateTimeString(),
            'place' => 'Казань',
            'description' => 'Обсуждение книг',
            'min_entries' => 0,
            'max_entries' => 10,
            'image' => 'events/test.jpg',
            'latitude' => 55.7887,
            'longitude' => 49.1221,
            'tags' => ['discussion'],
            'club_id' => $club->id,
            'author_id' => $club->id,
        ]);

        Event::create([
            'title' => 'Лекция по литературе',
            'date' => now()->addDays(2)->toDateTimeString(),
            'place' => 'Москва',
            'description' => 'Лекция',
            'min_entries' => 0,
            'max_entries' => 10,
            'image' => 'events/test2.jpg',
            'latitude' => 55.7558,
            'longitude' => 37.6173,
            'tags' => ['lecture'],
            'club_id' => $club->id,
            'author_id' => $club->id,
        ]);

        $this->actingAs($club);

        $response = $this->get('/event-index?q=книжная&date_from=' . now()->toDateString() . '&date_to=' . now()->addDays(2)->toDateString() . '&sort=popular&tags[]=discussion');

        $response->assertOk();
        $response->assertSee('Книжная встреча');
        $response->assertDontSee('Лекция по литературе');
    }

    public function test_event_index_can_filter_events_by_date_range(): void
    {
        $club = User::create([
            'username' => 'Клуб2',
            'email' => 'club2@example.com',
            'password' => 'password',
            'role' => 'club',
        ]);

        Event::create([
            'title' => 'Мероприятие в диапазоне',
            'date' => now()->addDays(3)->toDateTimeString(),
            'place' => 'Казань',
            'description' => 'Подходит по диапазону',
            'min_entries' => 0,
            'max_entries' => 10,
            'image' => 'events/test3.jpg',
            'latitude' => 55.7887,
            'longitude' => 49.1221,
            'tags' => ['discussion'],
            'club_id' => $club->id,
            'author_id' => $club->id,
        ]);

        Event::create([
            'title' => 'Мероприятие вне диапазона',
            'date' => now()->addDays(10)->toDateTimeString(),
            'place' => 'Москва',
            'description' => 'Не подходит по диапазону',
            'min_entries' => 0,
            'max_entries' => 10,
            'image' => 'events/test4.jpg',
            'latitude' => 55.7558,
            'longitude' => 37.6173,
            'tags' => ['lecture'],
            'club_id' => $club->id,
            'author_id' => $club->id,
        ]);

        $this->actingAs($club);

        $response = $this->get('/event-index?date_from=' . now()->addDays(2)->toDateString() . '&date_to=' . now()->addDays(5)->toDateString());

        $response->assertOk();
        $response->assertSee('Мероприятие в диапазоне');
        $response->assertDontSee('Мероприятие вне диапазона');
    }

    public function test_event_lists_hide_past_dates_but_keep_today_and_future(): void
    {
        $club = User::create([
            'username' => 'Date filter club',
            'email' => 'date-filter-club@example.test',
            'password' => 'password',
            'role' => 'club',
        ]);

        foreach ([
            'Прошедшее мероприятие' => now()->subDay(),
            'Мероприятие сегодня' => now(),
            'Будущее мероприятие' => now()->addDay(),
        ] as $title => $date) {
            Event::create([
                'title' => $title,
                'date' => $date->toDateTimeString(),
                'place' => 'Казань',
                'description' => 'Проверка даты',
                'min_entries' => 0,
                'max_entries' => 10,
                'image' => '',
                'latitude' => 55.7887,
                'longitude' => 49.1221,
                'club_id' => $club->id,
                'author_id' => $club->id,
            ]);
        }

        foreach ([route('event.index'), route('home')] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertDontSee('Прошедшее мероприятие')
                ->assertSee('Мероприятие сегодня')
                ->assertSee('Будущее мероприятие');
        }
    }
}
