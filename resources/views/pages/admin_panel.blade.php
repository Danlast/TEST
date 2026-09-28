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
        <div class="filter-grid">
            <div class="filter-field">
            <label for="admin-user-search">Поиск пользователя</label>
            <input type="search" id="admin-user-search" name="q" value="{{ $query }}" placeholder="Имя или email" autocomplete="off">
            </div>
            <div class="filter-field">
                <label for="admin-role-filter">Роль</label>
                <select id="admin-role-filter" name="role">
                    <option value="">Все роли</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->value }}" {{ $roleFilter === $role->value ? 'selected' : '' }}>{{ $role->value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label for="admin-club-filter">Клуб</label>
                <select id="admin-club-filter" name="club_id">
                    <option value="">Все клубы</option>
                    @foreach($clubs as $club)
                        <option value="{{ $club->id }}" {{ (string) $clubFilter === (string) $club->id ? 'selected' : '' }}>{{ $club->username }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label for="admin-privacy-filter">Приватность</label>
                <select id="admin-privacy-filter" name="privacy">
                    <option value="">Любой профиль</option>
                    <option value="public" {{ $privacyFilter === 'public' ? 'selected' : '' }}>Открытый</option>
                    <option value="private" {{ $privacyFilter === 'private' ? 'selected' : '' }}>Закрытый</option>
                </select>
            </div>
            <div class="filter-field">
                <label for="admin-banned-filter">Глобальный бан</label>
                <select id="admin-banned-filter" name="banned">
                    <option value="">Любой статус</option>
                    <option value="yes" {{ $bannedFilter === 'yes' ? 'selected' : '' }}>Заблокирован</option>
                    <option value="no" {{ $bannedFilter === 'no' ? 'selected' : '' }}>Не заблокирован</option>
                </select>
            </div>
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">Найти</button>
            @if($query !== '' || $roleFilter || $clubFilter || $privacyFilter || $bannedFilter)
                <a href="{{ route('admin.panel') }}" class="btn btn-outline">Сбросить</a>
            @endif
        </div>
    </form>

    <div class="admin-user-list">
        @forelse($users as $user)
            <article class="admin-user-row">
                <div class="admin-user-info">
                    @if($user->role === \App\Enums\UserRole::CLUB)
                        <a href="{{ route('club.profile', $user) }}" class="admin-user-link">{{ $user->username }}</a>
                    @else
                        <a href="{{ route('user.profile', $user) }}" class="admin-user-link">{{ $user->username }}</a>
                    @endif
                    <span>{{ $user->email }}</span>
                    <small>
                        Текущая роль: {{ $user->role?->value ?? 'не задана' }}
                        @if($user->club)
                            · Клуб: {{ $user->club->username }}
                        @endif
                    </small>
                </div>

                <form method="POST" action="{{ route('admin.users.update', $user) }}" class="admin-user-form" onsubmit="return confirm('Изменить роль или клуб пользователя?');">
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

    <hr>
    <h3>Журнал действий</h3>
    <div class="admin-audit-list">
        @forelse($auditLogs as $log)
            <div class="admin-audit-row">
                <strong>{{ $log->action }}</strong>
                <span>{{ $log->actor->username ?? 'Системный пользователь' }}</span>
                <time datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->format('d.m.Y H:i') }}</time>
            </div>
        @empty
            <p class="empty-hint">Действий пока нет.</p>
        @endforelse
    </div>
    {{ $auditLogs->links() }}
</section>
@endsection
