<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    public function store(Request $request, $id)
    {
        if (! Auth::check()) {
            abort(403);
        }

        if ($request->query('type') === 'article') {
            $article = Article::query()->where('is_published', true)->findOrFail($id);
            $user = Auth::user();

            if (! $user->canAccessClubContent($article->club_id)) {
                return back()->with('error', 'Вы были забанены в данном клубе.');
            }

            $like = $article->likes()->where('users.id', $user->id)->first();
            if ($like) {
                $article->likes()->detach($user->id);

                return redirect()->to(url()->previous() . '#article-engagement')->with('success', 'Лайк удалён.');
            }

            $article->likes()->attach($user->id);

            return redirect()->to(url()->previous() . '#article-engagement')->with('success', 'Статья понравилась вам.');
        }

        $event = Event::findOrFail($id);
        $userId = Auth::id();
        $user = Auth::user();

        if ($event->registered_count >= $event->max_entries) {
            return back()->with('error', 'Места на мероприятие закончились.');
        }

        if (! $user->canParticipateInClubEvent($event)) {
            return back()->with('error', 'Вы забанены в этом клубе и не можете записаться на его мероприятие.');
        }

        if (EventRegistration::where('user_id', $userId)->where('event_id', $id)->exists()) {
            return back()->with('error', 'Вы уже записаны на это мероприятие.');
        }

        EventRegistration::create([
            'user_id' => $userId,
            'event_id' => $id,
        ]);

        return back()->with('success', 'Вы успешно записались на мероприятие.');
    }

    public function destroy(Request $request, $eventId)
    {
        $event = Event::with('club')->findOrFail($eventId);
        $actor = Auth::user();

        if ($request->filled('user_id')) {
            abort_unless($actor && $actor->canManageEvent($event), 403);

            $data = $request->validate([
                'user_id' => ['required', 'integer', 'exists:users,id'],
            ]);

            abort_if((int) $data['user_id'] === (int) $actor->id, 403);

            $registration = EventRegistration::query()
                ->where('event_id', $event->id)
                ->where('user_id', $data['user_id'])
                ->first();

            if (! $registration) {
                return back()->with('error', 'Пользователь уже не записан на мероприятие.');
            }

            $registration->delete();

            return back()->with('success', 'Запись пользователя удалена.');
        }

        abort_unless((bool) $actor, 403);
        EventRegistration::query()
            ->where('user_id', $actor->id)
            ->where('event_id', $event->id)
            ->delete();

        return back()->with('success', 'Вы отменили запись на мероприятие.');
    }
}
