@extends('template.app')
@section('page')

<section class="content-shell form-card">
    <h2>Редактирование профиля</h2>
    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
        @csrf

        <div class="form-group">
            <label>Имя пользователя</label>
            <input type="text" name="username" value="{{ old('username', $user->username) }}" required>
            @error('username') <span class="error">* {{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
            @error('email') <span class="error">* {{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label>Описание</label>
            <textarea name="description" rows="5" maxlength="1000" placeholder="Напишите немного о себе (до 1000 символов)">{{ old('description', $user->description) }}</textarea>
            <div class="form-help profile-help">До 1000 символов</div>
            @error('description') <span class="error">* {{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label for="profile-privacy">Видимость профиля</label>
            <select id="profile-privacy" name="is_profile_private" required>
                <option value="0" {{ !old('is_profile_private', $user->is_profile_private) ? 'selected' : '' }}>Открытый для всех</option>
                <option value="1" {{ old('is_profile_private', $user->is_profile_private) ? 'selected' : '' }}>Закрытый</option>
            </select>
            <small class="form-help">Закрытый профиль и все его разделы доступны только вам.</small>
            @error('is_profile_private') <span class="error">* {{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label>Аватар</label>
            <input type="file" name="avatar" accept="image/*">
            @error('avatar') <span class="error">* {{ $message }}</span> @enderror
        </div>

        <input type="submit" class="btn btn-primary" value="Сохранить">
    </form>
</section>

@endsection
