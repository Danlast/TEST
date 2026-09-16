<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;    // Для хеширования пароля
use App\Models\Comment;
use App\Models\User;                    // Для работы с моделью User
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class AccountController extends Controller
{
    public function showReg(){
        return view('pages.reg');
    }

    public function sendReg(Request $request){
        $data = $request->validate([
            'username' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'password_confirmation' => 'required|min:6',
        ]);

        // запись в БД
        $data['password'] = Hash::make($data['password']);
        User::create($data);

        return redirect('/')->with('success','Успешная регистрация');
    }

    // ========== АВТОРИЗАЦИЯ ==========
    public function showLogin(){
        return view('pages.login');
    }

    public function sendLogin(Request $request){
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect('/')->with('success', 'Добро пожаловать!');
        }

        return back()->withErrors([
            'email' => 'Пользователь не найден или неверный пароль',
        ])->onlyInput('email');
    }

    public function logout(Request $request){
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/')->with('success', 'Вы вышли из системы');
    }

    public function profile() {
        $user = Auth::user();
        $events = $user->registeredEvents()->get();
        $bookedExchanges = \App\Models\BookExchange::where('booked_by_user_id', $user->id)->latest()->get();
        $joinedClubs = $user->joinedClubs()->orderBy('username')->get();
        $clubEvents = $user->clubEvents()->orderByDesc('created_at')->get();
        $comments = $user->comments()->with('user')->latest()->get();

        return view('pages.profile', compact('user', 'events', 'bookedExchanges', 'joinedClubs', 'clubEvents', 'comments'));
    }

    public function editProfile()
    {
        $user = Auth::user();

        return view('pages.profile_edit', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'username' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'description' => 'nullable|string|max:1000',
            'avatar' => 'nullable|image|max:5000',
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                $oldAvatarPath = $user->avatar_path;
                if ($oldAvatarPath) {
                    Storage::disk('public')->delete($oldAvatarPath);
                }
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = ltrim($path, '/');
        }

        $user->fill($data);
        $user->save();

        return redirect()->route('profile')->with('success', 'Профиль обновлён.');
    }

    public function storeComment(Request $request, $userId)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $profileUser = User::findOrFail($userId);

        if (! Auth::check()) {
            abort(403);
        }

        Comment::create([
            'user_id' => Auth::id(),
            'profile_user_id' => $profileUser->id,
            'content' => $request->input('content'),
        ]);

        return back()->with('success', 'Комментарий добавлен.');
    }

    public function destroyComment(Comment $comment)
    {
        if (! Auth::check()) {
            abort(403);
        }

        if (! Auth::user()->canDeleteComment($comment)) {
            abort(403);
        }

        $comment->delete();

        return back()->with('success', 'Комментарий удалён.');
    }

    public function avatar($path)
    {
        $filePath = storage_path('app/public/' . $path);

        if (! file_exists($filePath)) {
            abort(404);
        }

        return response()->file($filePath);
    }

    public function showUserProfile($id)
    {
        $user = User::findOrFail($id);
        $events = $user->registeredEvents()->get();
        $bookedExchanges = \App\Models\BookExchange::where('booked_by_user_id', $user->id)->latest()->get();
        $joinedClubs = $user->joinedClubs()->orderBy('username')->get();
        $clubEvents = $user->clubEvents()->orderByDesc('created_at')->get();

        $comments = $user->comments()->with('user')->latest()->get();

        return view('pages.user_profile', compact('user', 'events', 'bookedExchanges', 'joinedClubs', 'clubEvents', 'comments'));
    }

}