<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $query = trim((string) $request->input('q', ''));
        $role = $request->input('role');
        $clubId = $request->input('club_id');
        $privacy = $request->input('privacy');
        $banned = $request->input('banned');

        $users = User::query()
            ->with('club')
            ->when($query !== '', function ($usersQuery) use ($query) {
                $usersQuery->where(function ($searchQuery) use ($query) {
                    $searchQuery
                        ->where('username', 'like', '%' . $query . '%')
                        ->orWhere('email', 'like', '%' . $query . '%');
                });
            })
            ->when($role && UserRole::tryFrom($role), fn ($usersQuery) => $usersQuery->where('role', $role))
            ->when($clubId !== null && $clubId !== '', fn ($usersQuery) => $usersQuery->where('club_id', $clubId))
            ->when($privacy === 'private', fn ($usersQuery) => $usersQuery->where('is_profile_private', true))
            ->when($privacy === 'public', fn ($usersQuery) => $usersQuery->where('is_profile_private', false))
            ->when($banned === 'yes', fn ($usersQuery) => $usersQuery->where('role', UserRole::BAN))
            ->when($banned === 'no', fn ($usersQuery) => $usersQuery->where('role', '!=', UserRole::BAN))
            ->orderBy('username')
            ->paginate(20);

        $users->appends($request->query());

        $auditLogs = AuditLog::with('actor')->latest()->paginate(20, ['*'], 'logs_page');

        return view('pages.admin_panel', [
            'users' => $users,
            'query' => $query,
            'roleFilter' => $role,
            'clubFilter' => $clubId,
            'privacyFilter' => $privacy,
            'bannedFilter' => $banned,
            'roles' => UserRole::cases(),
            'clubs' => User::query()
                ->where('role', UserRole::CLUB)
                ->orderBy('username')
                ->limit(100)
                ->get(['id', 'username']),
            'auditLogs' => $auditLogs,
        ]);
    }

    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => ['required', Rule::enum(UserRole::class)],
            'club_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $role = UserRole::from($validated['role']);

        $oldValues = [
            'role' => $user->role?->value,
            'club_id' => $user->club_id,
        ];

        if ($user->is(Auth::user()) && $role !== UserRole::ADMIN) {
            return back()->withErrors([
                'role' => 'Нельзя снять роль администратора у самого себя.',
            ]);
        }

        if ($user->role === UserRole::ADMIN && $role !== UserRole::ADMIN) {
            $admins = User::where('role', UserRole::ADMIN)->count();
            if ($admins <= 1) {
                return back()->withErrors(['role' => 'Нельзя снять роль последнего администратора.']);
            }
        }

        $clubId = null;

        if ($role === UserRole::CLUB_MODERATOR) {
            if (empty($validated['club_id'])) {
                return back()->withErrors([
                    'club_id' => 'Для клубного модератора нужно выбрать клуб.',
                ]);
            }

            $clubId = User::query()
                ->whereKey($validated['club_id'])
                ->where('role', UserRole::CLUB)
                ->exists()
                ? (int) $validated['club_id']
                : null;

            if (! $clubId) {
                return back()->withErrors([
                    'club_id' => 'Выбранный пользователь не является клубом.',
                ]);
            }
        }

        $user->forceFill([
            'role' => $role,
            'club_id' => $clubId,
        ])->save();

        AuditLog::create([
            'actor_id' => Auth::id(),
            'action' => 'user.role_updated',
            'target_type' => User::class,
            'target_id' => $user->id,
            'old_values' => $oldValues,
            'new_values' => ['role' => $role->value, 'club_id' => $clubId],
        ]);

        return back()->with('success', 'Пользователь обновлён.');
    }
}