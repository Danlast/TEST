<?php

namespace App\Http\Controllers;

use App\Models\ClubMembership;
use App\Models\AuditLog;
use App\Models\Article;
use App\Models\Comment;
use App\Enums\UserRole;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ClubController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->input('q');

        $clubs = User::query()
            ->where('role', UserRole::CLUB)
            ->when($query, function ($q) use ($query) {
                $q->where(function ($sub) use ($query) {
                    $sub->where('username', 'like', '%' . $query . '%')
                        ->orWhere('email', 'like', '%' . $query . '%');
                });
            })
            ->orderBy('username')
            ->paginate(12);
        $clubs->appends($request->query());

        return view('pages.clubs.club_index', compact('clubs', 'query'));
    }

    public function profile(Request $request, $id)
    {
        $club = User::findOrFail($id);

        if ($club->role !== UserRole::CLUB) {
            abort(404);
        }

        if (
            Auth::check()
            && ! Auth::user()->role?->isStaff()
            && Auth::user()->isBannedFromClub($club->id)
            && Auth::id() !== $club->id
        ) {
            return response()->view('pages.club_banned', [
                'club' => $club,
                'banReason' => Auth::user()->club_ban_reason,
            ], 403);
        }

        $isMember = Auth::check() && ClubMembership::query()
            ->where('user_id', Auth::id())
            ->where('club_id', $club->id)
            ->exists();
        $isClubModerator = Auth::check()
            && Auth::user()->role === UserRole::CLUB_MODERATOR
            && (int) Auth::user()->club_id === (int) $club->id;

        $query = $request->input('member');

        $members = User::query()
            ->whereHas('clubMemberships', function ($q) use ($club) {
                $q->where('club_id', $club->id);
            })
            ->where('id', '!=', $club->id)
            ->where(function ($q) use ($club) {
                $q->where('club_banned', false)
                    ->orWhere('club_ban_club_id', '!=', $club->id)
                    ->orWhereNull('club_ban_club_id');
            })
            ->when($query, function ($q) use ($query) {
                $q->where(function ($sub) use ($query) {
                    $sub->where('username', 'like', '%' . $query . '%')
                        ->orWhere('email', 'like', '%' . $query . '%');
                });
            })
            ->orderBy('username')
            ->get();

        $eventQuery = $request->input('event');
        $events = Event::where('club_id', $club->id)
            ->when($eventQuery, function ($q) use ($eventQuery) {
                $q->where(function ($sub) use ($eventQuery) {
                    $sub->where('title', 'like', '%' . $eventQuery . '%')
                        ->orWhere('place', 'like', '%' . $eventQuery . '%');
                });
            })
            ->orderByDesc('created_at')
            ->paginate(6)
            ->appends($request->query());

        $moderators = User::query()
            ->where('role', UserRole::CLUB_MODERATOR)
            ->where('club_id', $club->id)
            ->orderBy('username')
            ->get(['id', 'username']);

        $comments = \App\Models\Comment::nestReplies($club->profileComments()->with(['user', 'repliedTo.user'])->oldest()->get());

        return view('pages.clubs.club_profile', compact('club', 'members', 'moderators', 'events', 'comments', 'query', 'eventQuery', 'isMember', 'isClubModerator'));
    }

    public function join($id)
    {
        $club = User::findOrFail($id);

        if ($club->role !== UserRole::CLUB) {
            abort(404);
        }

        $user = Auth::user();

        abort_if($user->isBannedFromClub($club->id), 403, 'Вы заблокированы в этом клубе.');

        ClubMembership::firstOrCreate([
            'user_id' => $user->id,
            'club_id' => $club->id,
        ]);

        return back()->with('success', 'Вы присоединились к клубу.');
    }

    public function leave($id)
    {
        $club = User::findOrFail($id);

        if ($club->role !== UserRole::CLUB) {
            abort(404);
        }

        $user = Auth::user();

        if ($user->role === UserRole::CLUB_MODERATOR) {
            return $this->leaveAsModerator($club->id);
        }

        ClubMembership::where('user_id', $user->id)
            ->where('club_id', $club->id)
            ->delete();

        return back()->with('success', 'Вы покинули клуб.');
    }

    public function leaveAsModerator($id)
    {
        $club = User::query()->whereKey($id)->where('role', UserRole::CLUB)->firstOrFail();
        $moderator = Auth::user();

        abort_unless(
            $moderator->role === UserRole::CLUB_MODERATOR
            && (int) $moderator->club_id === (int) $club->id,
            403
        );

        DB::transaction(function () use ($club, $moderator) {
            $moderator->forceFill([
                'role' => UserRole::USER,
                'club_id' => null,
            ])->save();

            ClubMembership::query()
                ->where('user_id', $moderator->id)
                ->where('club_id', $club->id)
                ->delete();

            AuditLog::create([
                'actor_id' => $moderator->id,
                'action' => 'club.moderator_left',
                'target_type' => User::class,
                'target_id' => $moderator->id,
                'old_values' => ['role' => UserRole::CLUB_MODERATOR->value, 'club_id' => $club->id],
                'new_values' => ['role' => UserRole::USER->value, 'club_id' => null],
            ]);
        });

        return redirect()->route('club.profile', $club)->with('success', 'Вы покинули клуб и больше не являетесь его модератором.');
    }

    public function editProfile($id)
    {
        $club = User::findOrFail($id);

        abort_unless(Auth::user()->canEditClubProfile($club), 403);

        return view('pages.clubs.club_edit', compact('club'));
    }

    public function updateProfile(Request $request, $id)
    {
        $club = User::findOrFail($id);

        abort_unless(Auth::user()->canEditClubProfile($club), 403);

        $data = $request->validate([
            'username' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $club->id,
            'description' => 'nullable|string|max:1000',
            'avatar' => 'nullable|image|max:5000',
        ]);

        if ($request->hasFile('avatar')) {
            $oldAvatarPath = $club->avatar_path;
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = ltrim($path, '/');

            if ($oldAvatarPath) {
                Storage::disk('public')->delete($oldAvatarPath);
            }
        }

        $club->fill($data);
        $club->save();

        AuditLog::create([
            'actor_id' => Auth::id(),
            'action' => 'club.profile_updated',
            'target_type' => User::class,
            'target_id' => $club->id,
        ]);

        return redirect()->route('club.profile', $club->id)->with('success', 'Профиль клуба обновлён.');
    }

    public function banUser(Request $request, $id)
    {
        $club = User::findOrFail($id);

        if (!Auth::user()->canManageClub($club)) {
            abort(403);
        }

        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'email' => 'nullable|email|required_without:user_id|exists:users,email',
            'reason' => 'nullable|string|max:255',
        ]);

        $targetUser = $request->filled('user_id')
            ? User::findOrFail($request->input('user_id'))
            : User::where('email', $request->input('email'))->firstOrFail();

        if ($targetUser->id === $club->id) {
            return back()->withErrors(['user_id' => 'Нельзя забанить самого клуба.']);
        }

        if (Auth::user()->role === UserRole::CLUB_MODERATOR && $targetUser->role !== UserRole::USER) {
            abort(403, 'Модератор клуба может блокировать только обычных пользователей.');
        }

        $this->banFromClub($club, $targetUser, $request->input('reason'));

        return back()->with('success', 'Пользователь забанен в клубе.');
    }

    public function assignRole(Request $request, $id)
    {
        $club = User::findOrFail($id);

        if (!Auth::user()->canManageClub($club)) {
            abort(403);
        }

        $data = $request->validate([
            'email' => 'required|email|exists:users,email',
            'action' => ['required', \Illuminate\Validation\Rule::in([
                UserRole::CLUB_MODERATOR->value,
                UserRole::USER->value,
                'ban',
                'unban',
            ])],
            'reason' => 'required_if:action,ban|nullable|string|max:255',
        ]);

        if (in_array($data['action'], [UserRole::CLUB_MODERATOR->value, UserRole::USER->value], true)) {
            abort_unless(Auth::user()->canEditClubProfile($club), 403);
        }

        $targetUser = User::where('email', $data['email'])->firstOrFail();

        if ($data['action'] === 'ban') {
            if (Auth::user()->role === UserRole::CLUB_MODERATOR && $targetUser->role !== UserRole::USER) {
                abort(403, 'Модератор клуба может блокировать только обычных пользователей.');
            }

            if ($targetUser->id === $club->id) {
                return back()->withErrors(['email' => 'Нельзя забанить самого клуба.']);
            }

            $this->banFromClub($club, $targetUser, $data['reason'] ?? null);

            return back()->with('success', 'Пользователь забанен в клубе.');
        }

        if ($data['action'] === 'unban') {
            if (Auth::user()->role === UserRole::CLUB_MODERATOR && $targetUser->role !== UserRole::USER) {
                abort(403, 'Модератор клуба может разблокировать только обычных пользователей.');
            }

            if (! $targetUser->isBannedFromClub((int) $club->id)) {
                return back()->withErrors(['email' => 'Пользователь не заблокирован в этом клубе.']);
            }

            $oldBanReason = $targetUser->club_ban_reason;
            $targetUser->forceFill([
                'club_banned' => false,
                'club_ban_club_id' => null,
                'club_ban_reason' => null,
            ])->save();

            AuditLog::create([
                'actor_id' => Auth::id(),
                'action' => 'club.user_unbanned',
                'target_type' => User::class,
                'target_id' => $targetUser->id,
                'old_values' => ['club_id' => $club->id, 'reason' => $oldBanReason],
                'new_values' => ['club_id' => null, 'reason' => null],
            ]);

            return back()->with('success', 'Пользователь разблокирован в клубе.');
        }

        $targetRole = UserRole::from($data['action']);
        $targetUser->role = $targetRole;
        $targetUser->club_id = $targetRole === UserRole::CLUB_MODERATOR ? $club->id : null;
        $targetUser->save();

        AuditLog::create([
            'actor_id' => Auth::id(),
            'action' => 'club.role_assigned',
            'target_type' => User::class,
            'target_id' => $targetUser->id,
            'new_values' => ['role' => $targetRole->value, 'club_id' => $targetUser->club_id],
        ]);

        return back()->with('success', 'Роль назначена.');
    }

    private function banFromClub(User $club, User $targetUser, ?string $reason): void
    {
        $clubEventIds = Event::query()->where('club_id', $club->id)->select('id');
        $clubArticleIds = Article::query()->where('club_id', $club->id)->select('id');

        DB::transaction(function () use ($club, $targetUser, $reason, $clubEventIds, $clubArticleIds) {
            $targetUser->forceFill([
                'club_banned' => true,
                'club_ban_reason' => $reason,
                'club_ban_club_id' => $club->id,
            ])->save();

            ClubMembership::query()
                ->where('user_id', $targetUser->id)
                ->where('club_id', $club->id)
                ->delete();

            EventRegistration::query()
                ->where('user_id', $targetUser->id)
                ->whereIn('event_id', $clubEventIds)
                ->delete();

            $comments = Comment::query()
                ->where('user_id', $targetUser->id)
                ->where(function ($query) use ($club, $clubEventIds, $clubArticleIds) {
                    $query->where('profile_user_id', $club->id)
                        ->orWhereIn('event_id', $clubEventIds)
                        ->orWhereIn('article_id', $clubArticleIds);
                })
                ->get();

            foreach ($comments as $comment) {
                if ($comment->replies()->exists() || $comment->replyReferences()->exists()) {
                    $comment->update(['content' => Comment::DELETED_CONTENT]);
                } else {
                    $comment->delete();
                }
            }

            AuditLog::create([
                'actor_id' => Auth::id(),
                'action' => 'club.user_banned',
                'target_type' => User::class,
                'target_id' => $targetUser->id,
                'new_values' => ['club_id' => $club->id, 'reason' => $reason],
            ]);
        });
    }
}
