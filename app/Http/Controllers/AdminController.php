<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $query = trim((string) $request->input('q', ''));

        $users = User::query()
            ->with('club')
            ->when($query !== '', function ($usersQuery) use ($query) {
                $usersQuery->where(function ($searchQuery) use ($query) {
                    $searchQuery
                        ->where('username', 'like', '%' . $query . '%')
                        ->orWhere('email', 'like', '%' . $query . '%');
                });
            })
            ->orderBy('username')
            ->paginate(20);

        $users->appends($request->query());

        return view('pages.admin_panel', [
            'users' => $users,
            'query' => $query,
            'roles' => UserRole::cases(),
            'clubs' => User::query()
                ->where('role', UserRole::CLUB)
                ->orderBy('username')
                ->get(['id', 'username']),
        ]);
    }

    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => ['required', Rule::enum(UserRole::class)],
            'club_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $role = UserRole::from($validated['role']);

        if ($user->is(Auth::user()) && $role !== UserRole::ADMIN) {
            return back()->withErrors([
                'role' => 'Нельзя снять роль администратора у самого себя.',
            ]);
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

        return back()->with('success', 'Пользователь обновлён.');
    }
}