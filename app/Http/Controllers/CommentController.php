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
    private const DELETED_CONTENT = 'Комментарий был удален';

    public static function createdResponse(Request $request, Comment $comment, string $message, int $depth = 0)
    {
        if (! $request->expectsJson()) {
            return back()->with('success', $message);
        }

        $comment->load(['user', 'repliedTo.user']);
        $comment->setRelation('replies', collect());

        return response()->json([
            'html' => view('components.comment-item', compact('comment', 'depth'))->render(),
            'message' => $message,
        ]);
    }

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

        $comment = Comment::create([
            'user_id' => Auth::id(),
            'event_id' => $event->id,
            'content' => $request->input('content'),
        ]);

        return self::createdResponse($request, $comment, 'Комментарий добавлен.');
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

        $comment = Comment::create([
            'user_id' => Auth::id(),
            'profile_user_id' => $user->id,
            'content' => $request->input('content'),
        ]);

        return self::createdResponse($request, $comment, 'Комментарий добавлен.');
    }

    public function reply(Request $request, Comment $comment)
    {
        $data = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $user = Auth::user();
        $depth = 0;
        $ancestor = $comment;
        while ($ancestor->parent_id !== null) {
            $depth++;
            $ancestor = Comment::query()->select(['id', 'parent_id'])->findOrFail($ancestor->parent_id);
        }

        abort_if($depth > 5, 422, 'Достигнута максимальная глубина ответов.');

        $context = [
            'event_id' => $comment->event_id,
            'profile_user_id' => $comment->profile_user_id,
            'article_id' => $comment->article_id,
        ];

        if ($comment->event_id) {
            abort_unless($user->canParticipateInClubEvent($comment->event), 403, 'Вы забанены в этом клубе и не можете отвечать на комментарии.');
        } elseif ($comment->profile_user_id) {
            $profileUser = $comment->profileUser;
            abort_unless($profileUser, 404);

            if ($profileUser->role === \App\Enums\UserRole::CLUB && $user->isBannedFromClub($profileUser->id)) {
                abort(403, 'Вы заблокированы в этом клубе.');
            }

            abort_unless($profileUser->canViewProfile($user), 404);
        } elseif ($comment->article_id) {
            $article = $comment->article;
            abort_unless($article, 404);
            abort_unless($user->canAccessClubContent($article->club_id), 403, 'Вы забанены в этом клубе и не можете отвечать на комментарии.');
        } else {
            abort(404);
        }

        $treeParentId = $depth === 5 ? $comment->parent_id : $comment->id;
        $reply = Comment::create($context + [
            'user_id' => $user->id,
            'parent_id' => $treeParentId,
            'reply_to_id' => $comment->id,
            'content' => $data['content'],
        ]);

        $reply->load(['user', 'repliedTo.user']);
        $reply->setRelation('replies', collect());

        if ($request->expectsJson()) {
            return response()->json([
                'html' => view('components.comment-item', ['comment' => $reply, 'depth' => min(5, $depth + 1)])->render(),
                'message' => 'Ответ добавлен.',
                'sameLevel' => $depth === 5,
            ]);
        }

        return back()->with('success', 'Ответ добавлен.');
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

        abort_if($comment->content === self::DELETED_CONTENT, 404);

        $comment->update([
            'content' => $request->input('content'),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['content' => $comment->content, 'message' => 'Комментарий обновлён.']);
        }

        return back()->with('success', 'Комментарий обновлён.');
    }

    public function destroy(Request $request, Comment $comment)
    {
        if (! Auth::check()) {
            abort(403);
        }

        if (! Auth::user()->canDeleteComment($comment)) {
            abort(403);
        }

        $hasReplies = $comment->replies()->exists() || $comment->replyReferences()->exists();

        if ($hasReplies) {
            $comment->update(['content' => self::DELETED_CONTENT]);
        } else {
            $comment->delete();
        }

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

        if ($request->expectsJson()) {
            return response()->json([
                'tombstone' => $hasReplies,
                'message' => $hasReplies ? 'Комментарий скрыт.' : 'Комментарий удалён.',
            ]);
        }

        return back()->with('success', $hasReplies ? 'Комментарий скрыт.' : 'Комментарий удалён.');
    }
}
