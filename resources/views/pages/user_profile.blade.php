@extends('template.app')
@section('page')

<section class="content-shell profile-card">
    <h2>Профиль: {{ $user->username }}</h2>
    <p class="profile-email">{{ $user->email }}</p>

    <div class="profile-avatar-wrap">
        @php
            $avatarUrl = $user->avatar_url;
        @endphp
        @if($avatarUrl)
            <img src="{{ $avatarUrl }}" alt="Аватар" class="profile-avatar">
        @else
            <div class="profile-avatar profile-avatar-fallback">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z" fill="#8a5a20"/>
                    <path d="M4 20C4 16.6863 7.13401 14 11 14H13C16.866 14 20 16.6863 20 20V21H4V20Z" fill="#8a5a20"/>
                </svg>
                <span class="profile-initial">{{ strtoupper(substr($user->username, 0, 1)) }}</span>
            </div>
        @endif
    </div>

    @if(!empty($user->description))
        <div class="profile-description">
            <strong>Описание:</strong><br>
            {{ $user->description }}
        </div>
    @endif

    <hr>
    <h3>Мероприятия пользователя</h3>

    @if($events->isEmpty())
        <p>Пользователь ещё не записался на мероприятия.</p>
    @else
        <div class="favorite-list">
            @foreach($events as $event)
                <div class="fav-item">
                    <a href="{{ route('event.show', $event->id) }}">{{ $event->title }}</a>
                </div>
            @endforeach
        </div>
    @endif

    <hr>
    <h3>Забронированные обмены</h3>
    <div class="favorite-list">
        @if($bookedExchanges->isEmpty())
            <p>Пользователь ещё не бронировал обмены.</p>
        @else
            @foreach($bookedExchanges as $exchange)
                <div class="fav-item">
                    <a href="{{ route('exchange.show', $exchange->id) }}">{{ $exchange->title }}</a>
                </div>
            @endforeach
        @endif
    </div>

    <hr>
    <h3>Клубы</h3>
    <div class="favorite-list">
        @if($joinedClubs->isEmpty())
            <p>Пользователь пока не состоит ни в одном клубе.</p>
        @else
            @foreach($joinedClubs as $club)
                <div class="fav-item">
                    <a href="{{ route('club.profile', $club->id) }}">{{ $club->username }}</a>
                </div>
            @endforeach
        @endif
    </div>

    <hr>
    <h3>Мероприятия клубов пользователя</h3>
    <div class="favorite-list">
        @if($clubEvents->isEmpty())
            <p>У клубов пользователя пока нет мероприятий.</p>
        @else
            @foreach($clubEvents as $event)
                <div class="fav-item">
                    <a href="{{ route('event.show', $event->id) }}">{{ $event->title }}</a>
                </div>
            @endforeach
        @endif
    </div>

    <hr>
    <h3>Комментарии</h3>
    @auth
        <form method="POST" action="{{ route('comments.profile.store', $user) }}" class="comment-form">
            @csrf
            <textarea name="content" rows="3" placeholder="Оставьте комментарий" required></textarea>
            <button class="btn btn-primary form-submit">Отправить</button>
        </form>
    @endauth

    @if($comments->isNotEmpty())
        <div class="comments-list">
            @foreach($comments as $comment)
                <x-comment-item :comment="$comment" />
            @endforeach
        </div>
    @else
        <p class="empty-hint">Комментариев пока нет.</p>
    @endif
</section>

@endsection
