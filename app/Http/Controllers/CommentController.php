<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function storeEventComment(Request $request, Event $event)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        if (! Auth::check()) {
            abort(403);
        }

        $user = Auth::user();

        if (! $user->canParticipateInClubEvent($event)) {
            abort(403, 'Вы забанены в этом клубе и не можете оставлять комментарии под его мероприятиями.');
        }

        Comment::create([
            'user_id' => Auth::id(),
            'event_id' => $event->id,
            'content' => $request->input('content'),
        ]);

        return back()->with('success', 'Комментарий добавлен.');
    }

    public function storeProfileComment(Request $request, User $user)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        if (! Auth::check()) {
            abort(403);
        }

        if ($user->role === \App\Enums\UserRole::CLUB && Auth::user()->isBannedFromClub($user->id)) {
            abort(403, 'Вы заблокированы в этом клубе.');
        }

        abort_unless($user->canViewProfile(Auth::user()), 404);

        Comment::create([
            'user_id' => Auth::id(),
            'profile_user_id' => $user->id,
            'content' => $request->input('content'),
        ]);

        return back()->with('success', 'Комментарий добавлен.');
    }

    public function update(Request $request, Comment $comment)
    {
        if (! Auth::check()) {
            abort(403);
        }

        if (Auth::id() !== $comment->user_id) {
            abort(403);
        }

        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $comment->update([
            'content' => $request->input('content'),
        ]);

        return back()->with('success', 'Комментарий обновлён.');
    }

    public function destroy(Comment $comment)
    {
        if (! Auth::check()) {
            abort(403);
        }

        if (! Auth::user()->canDeleteComment($comment)) {
            abort(403);
        }

        $comment->delete();

        AuditLog::create([
            'actor_id' => Auth::id(),
            'action' => 'comment.deleted',
            'target_type' => Comment::class,
            'target_id' => $comment->id,
            'metadata' => [
                'event_id' => $comment->event_id,
                'profile_user_id' => $comment->profile_user_id,
                'article_id' => $comment->article_id,
            ],
        ]);

        return back()->with('success', 'Комментарий удалён.');
    }
}
