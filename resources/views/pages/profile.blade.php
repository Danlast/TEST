@extends('template.app')
@section('page')

<section class="content-shell profile-card">
    <div class="profile-header">
        <div class="profile-header-identity">
            <div class="profile-avatar-wrap">
                @if($user->avatar_url)
                    <img src="{{ $user->avatar_url }}" alt="Аватар {{ $user->username }}" class="profile-avatar">
                @else
                    <div class="profile-avatar profile-avatar-fallback">
                        <span class="profile-initial">{{ strtoupper(substr($user->username, 0, 1)) }}</span>
                    </div>
                @endif
            </div>
            <div class="profile-identity-text">
                <h2>Профиль: {{ $user->username }}</h2>
                <p class="profile-email">{{ $user->email }}</p>
            </div>
        </div>
        <a href="{{ route('profile.edit') }}" class="btn btn-outline">Редактировать профиль</a>
    </div>

    @if(!empty($user->description))
        <div class="profile-description">
            <strong>Описание:</strong><br>
            {{ $user->description }}
        </div>
    @endif

    <hr>

    <h3>Мероприятия, на которые вы записаны</h3>
    <div class="favorite-list">
        @if($events->isEmpty())
            <p>Вы ещё не записались на мероприятия.</p>
        @else
            @foreach($events as $event)
                <div class="fav-item">
                    <a href="{{ route('event.show', $event->id) }}">{{ $event->title }}</a>
                    <form action="{{ route('favorites.destroy', $event->id) }}" method="POST">
                        @csrf
                        <input class="btn btn-primary" type="submit" value="Отменить запись">
                    </form>
                </div>
            @endforeach
        @endif
    </div>

    <hr>

    <h3>Забронированные обмены</h3>
    <div class="favorite-list">
        @if($bookedExchanges->isEmpty())
            <p>Вы ещё не бронировали обмены.</p>
        @else
            @foreach($bookedExchanges as $exchange)
                <div class="fav-item">
                    <a href="{{ route('exchange.show', $exchange->id) }}">{{ $exchange->title }}</a>
                    <form action="{{ route('exchange.unbook', $exchange->id) }}" method="POST">
                        @csrf
                        <input class="btn btn-outline" type="submit" value="Отказаться">
                    </form>
                </div>
            @endforeach
        @endif
    </div>

    <hr>

    <h3>Клубы, в которые вы вступили</h3>
    <div class="favorite-list">
        @if($joinedClubs->isEmpty())
            <p>Вы пока не вступили ни в один клуб.</p>
        @else
            @foreach($joinedClubs as $club)
                <div class="fav-item">
                    <a href="{{ route('club.profile', $club->id) }}">{{ $club->username }}</a>
                </div>
            @endforeach
        @endif
    </div>

    <hr>

    <h3>Мероприятия клубов, к которым вы присоединились</h3>
    <div class="favorite-list">
        @if($clubEvents->isEmpty())
            <p>У ваших клубов пока нет мероприятий.</p>
        @else
            @foreach($clubEvents as $event)
                <div class="fav-item">
                    <a href="{{ route('event.show', $event->id) }}">{{ $event->title }}</a>
                </div>
            @endforeach
        @endif
    </div>

    <hr>

    <div data-comment-section>
    <h3>Комментарии</h3>
    @auth
        <form method="POST" action="{{ route('comments.profile.store', $user) }}" class="comment-form">
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