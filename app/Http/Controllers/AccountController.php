<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;    // Для хеширования пароля
use App\Models\Comment;
use App\Models\User;                    // Для работы с моделью User
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;


class AccountController extends Controller
{
    public function showReg(){
        return view('pages.reg');
    }

    public function sendReg(Request $request){
        $data = $request->validate([
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => 'required|min:6|confirmed',
            'password_confirmation' => 'required|min:6',
        ], [
            'username.unique' => 'Это имя пользователя уже занято.',
            'email.unique' => 'Этот адрес электронной почты уже зарегистрирован.',
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

        return view('pages.profile', $this->profileData($user));
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
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'description' => 'nullable|string|max:1000',
            'avatar' => 'nullable|image|max:5000',
            'is_profile_private' => 'sometimes|boolean',
        ], [
            'username.unique' => 'Это имя пользователя уже занято.',
            'email.unique' => 'Этот адрес электронной почты уже используется.',
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

        if (! $request->has('is_profile_private')) {
            $data['is_profile_private'] = (bool) ($user->is_profile_private ?? false);
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

        $comment = Comment::create([
            'user_id' => Auth::id(),
            'profile_user_id' => $profileUser->id,
            'content' => $request->input('content'),
        ]);

        return CommentController::createdResponse($request, $comment, 'Комментарий добавлен.');
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

        if (! $user->canViewProfile(Auth::user())) {
            return response()->view('pages.profile_hidden', [], 404);
        }

        return view('pages.user_profile', $this->profileData($user));
    }

    private function profileData(User $user): array
    {
        return [
            'user' => $user,
            'events' => $user->registeredEvents()->get(),
            'bookedExchanges' => \App\Models\BookExchange::where('booked_by_user_id', $user->id)->latest()->get(),
            'joinedClubs' => $user->joinedClubs()->orderBy('username')->get(),
            'clubEvents' => $user->clubEvents()->orderByDesc('created_at')->get(),
            'comments' => Comment::nestReplies($user->profileComments()->with(['user', 'repliedTo.user'])->oldest()->get()),
        ];
    }

}