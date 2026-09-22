@extends('template.app')

@section('page')
<section class="page-shell admin-panel">
    <div class="page-header">
        <div>
            <h2 class="section-title page-title">Панель администратора</h2>
            <p class="page-subtitle">Управление ролями и доступом пользователей.</p>
        </div>
        <span class="admin-stat">Пользователей: {{ $users->total() }}</span>
    </div>

    <form method="GET" action="{{ route('admin.panel') }}" class="filter-panel">
        <div class="filter-field">
            <label for="admin-user-search">Поиск пользователя</label>
            <input type="search" id="admin-user-search" name="q" value="{{ $query }}" placeholder="Имя или email" autocomplete="off">
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">Найти</button>
            @if($query !== '')
                <a href="{{ route('admin.panel') }}" class="btn btn-outline">Сбросить</a>
            @endif
        </div>
    </form>

    <div class="admin-user-list">
        @forelse($users as $user)
            <article class="admin-user-row">
                <div class="admin-user-info">
                    <strong>{{ $user->username }}</strong>
                    <span>{{ $user->email }}</span>
                    <small>
                        Текущая роль: {{ $user->role?->value ?? 'не задана' }}
                        @if($user->club)
                            · Клуб: {{ $user->club->username }}
                        @endif
                    </small>
                </div>

                <form method="POST" action="{{ route('admin.users.update', $user) }}" class="admin-user-form">
                    @csrf
                    <label>
                        <span class="sr-only">Роль пользователя {{ $user->username }}</span>
                        <select name="role">
                            @foreach($roles as $role)
                                <option value="{{ $role->value }}" {{ $user->role === $role ? 'selected' : '' }}>{{ $role->value }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span class="sr-only">Клуб пользователя {{ $user->username }}</span>
                        <select name="club_id">
                            <option value="">Без клуба</option>
                            @foreach($clubs as $club)
                                <option value="{{ $club->id }}" {{ (int) $user->club_id === $club->id ? 'selected' : '' }}>{{ $club->username }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </form>
            </article>
        @empty
            <p class="empty-hint">Пользователи не найдены.</p>
        @endforelse
    </div>

    {{ $users->links() }}
</section>
@endsection
