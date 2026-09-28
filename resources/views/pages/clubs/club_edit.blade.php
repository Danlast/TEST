@extends('template.app')

@section('page')
<section class="form-card">
    <h2>Редактировать профиль клуба</h2>

    <form method="POST" action="{{ route('club.update', $club->id) }}" class="form-group" enctype="multipart/form-data">
        @csrf

        <label>Название клуба</label>
        <input type="text" name="username" value="{{ old('username', $club->username) }}" required>
        @error('username')
            <span class="error">* {{ $message }}</span>
        @enderror

        <label>Email клуба</label>
        <input type="email" name="email" value="{{ old('email', $club->email) }}" required>
        @error('email')
            <span class="error">* {{ $message }}</span>
        @enderror

        <label>Описание клуба</label>
        <textarea name="description" rows="5" maxlength="1000" placeholder="Расскажите о клубе (до 1000 символов)">{{ old('description', $club->description) }}</textarea>
        @error('description')
            <span class="error">* {{ $message }}</span>
        @enderror

        <label>Аватар клуба</label>
        <input type="file" name="avatar" accept="image/*">
        @error('avatar')
            <span class="error">* {{ $message }}</span>
        @enderror

        <input type="submit" class="btn btn-primary" value="Сохранить">
    </form>
</section>
@endsection
