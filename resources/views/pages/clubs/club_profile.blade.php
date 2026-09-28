@extends('template.app')

@section('page')
<section class="content-shell club-profile-page">
    <div class="profile-header">
        <div class="profile-header-identity">
            <div class="profile-avatar-wrap">
                @if($club->avatar_url)
                    <img src="{{ $club->avatar_url }}" alt="Аватар клуба {{ $club->username }}" class="profile-avatar">
                @else
                    <div class="profile-avatar profile-avatar-fallback">
                        <span class="profile-initial">{{ strtoupper(substr($club->username, 0, 1)) }}</span>
                    </div>
                @endif
            </div>
            <div class="profile-identity-text">
                <h2>{{ $club->username }}</h2>
                <p class="profile-email"><strong>Email:</strong> {{ $club->email }}</p>
            </div>
        </div>
        @auth
            @if(auth()->user()->canManageClub($club))
                <a href="{{ route('club.edit', $club->id) }}" class="btn btn-outline">Редактировать профиль</a>
            @endif
        @endauth
    </div>

    @if(!empty($club->description))
        <div class="profile-description">
            <strong>Описание:</strong><br>
            {{ $club->description }}
        </div>
    @endif

    @auth
        @if(auth()->user()->id !== $club->id)
            @if(auth()->user()->club_id === $club->id)
                <form action="{{ route('club.leave', $club->id) }}" method="POST" class="flex">
                    @csrf
                    <input type="submit" class="btn btn-delete" value="Отписаться">
                </form>
            @else
                <form action="{{ route('club.join', $club->id) }}" method="POST" class="flex">
                    @csrf
                    <input type="submit" class="btn btn-primary" value="Присоединиться">
                </form>
            @endif
        @endif
    @endauth

    @if(auth()->user() && auth()->user()->canManageClub($club))
        <hr>
        <h3>Управление клубом</h3>
        <div class="flex">
            <a href="{{ route('event.create') }}" class="btn btn-primary">Создать мероприятие</a>
        </div>
    @endif

    <hr>
    <h3>Мероприятия клуба</h3>
    <form method="GET" class="filter-panel club-event-filter">
        @if($query)
            <input type="hidden" name="member" value="{{ $query }}">
        @endif
        <label for="club-event-search">Поиск мероприятий клуба</label>
        <div class="club-event-search-row">
            <input id="club-event-search" type="search" name="event" value="{{ $eventQuery ?? '' }}" placeholder="Название или место">
            <button type="submit" class="btn btn-outline">Найти</button>
            @if($eventQuery)
                <a href="{{ route('club.profile', ['id' => $club->id, 'member' => $query]) }}" class="btn btn-outline">Сбросить</a>
            @endif
        </div>
    </form>

    <div class="object-grid">
        @forelse($events as $event)
            <x-event-card :event="$event" />
        @empty
            <p>{{ $eventQuery ? 'По вашему запросу мероприятий не найдено.' : 'Мероприятий пока нет.' }}</p>
        @endforelse
    </div>
    @if($events->hasPages())
        {{ $events->links() }}
    @endif

    @if(auth()->user() && auth()->user()->canManageClub($club))
        <h3 class="club-participant-management">Управление участником</h3>
        <form action="{{ route('club.assignRole', $club->id) }}" method="POST" class="form-group" onsubmit="return confirm('Применить действие к пользователю?');">
            @csrf
            <label>Email пользователя</label>
            <input type="email" name="email" value="{{ old('email') }}" placeholder="user@example.com" required>
            <label>Действие</label>
            <select name="action" data-club-member-action required>
                <option value="club_moderator" @selected(old('action') === 'club_moderator')>Назначить модератором</option>
                <option value="user" @selected(old('action') === 'user')>Снять роль модератора</option>
                <option value="ban" @selected(old('action') === 'ban')>Забанить в клубе</option>
            </select>
            <div data-ban-reason hidden>
                <label for="club-ban-reason">Причина бана</label>
                <input id="club-ban-reason" type="text" name="reason" value="{{ old('reason') }}" maxlength="255" placeholder="Укажите причину бана" disabled>
                @error('reason') <span class="error">* {{ $message }}</span> @enderror
            </div>
            @error('email') <span class="error">* {{ $message }}</span> @enderror
            @error('action') <span class="error">* {{ $message }}</span> @enderror
            <input type="submit" class="btn btn-primary" value="Применить">
        </form>
        <script>
            const clubAction = document.querySelector('[data-club-member-action]');
            const banReason = document.querySelector('[data-ban-reason]');
            const banReasonInput = banReason?.querySelector('input[name="reason"]');

            if (clubAction && banReason && banReasonInput) {
                const updateBanReason = () => {
                    const isBan = clubAction.value === 'ban';
                    banReason.hidden = !isBan;
                    banReasonInput.disabled = !isBan;
                    banReasonInput.required = isBan;
                };

                clubAction.addEventListener('change', updateBanReason);
                updateBanReason();
            }
        </script>
    @endif

    <hr>
    <h3>Участники клуба</h3>
    <form method="GET" class="form-group">
        @if($eventQuery)
            <input type="hidden" name="event" value="{{ $eventQuery }}">
        @endif
        <input type="text" name="member" value="{{ $query ?? '' }}" placeholder="Поиск участника">
        <input type="submit" class="btn btn-outline" value="Найти">
    </form>

    @if($members->isNotEmpty())
        <ul>
            @foreach($members as $member)
                <li>
                    <a href="{{ route('user.profile', $member->id) }}">{{ $member->username }}</a>
                </li>
            @endforeach
        </ul>
    @else
        <p>Участников пока нет.</p>
    @endif

    <hr>
    <div data-comment-section>
    <h3>Комментарии</h3>
    @auth
        <form method="POST" action="{{ route('comments.profile.store', $club) }}" class="comment-form">
            @csrf
            <textarea name="content" rows="3" placeholder="Оставьте комментарий" required></textarea>
            <button class="btn btn-primary form-submit">Отправить</button>
        </form>
    @endauth

    <div class="comments-list" data-comments-list>
        @if($comments->isNotEmpty())
            @foreach($comments as $comment)
                <x-comment-item :comment="$comment" :depth="0" />
            @endforeach
        @endif
    </div>
    @if($comments->isEmpty())
        <p class="empty-hint">Комментариев пока нет.</p>
    @endif
    </div>
</section>
@endsection
