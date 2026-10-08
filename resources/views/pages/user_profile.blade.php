@extends('template.app')
@section('page')

<section class="content">
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
                <h2>{{ $user->username }}</h2>
                <p class="profile-email">{{ $user->email }}</p>
                @if($user->role === \App\Enums\UserRole::CLUB_MODERATOR && $user->club)
                    <p class="profile-club">Модератор клуба: <a href="{{ route('club.profile', $user->club) }}">{{ $user->club->username }}</a></p>
                @endif
            </div>
        </div>
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
        <div class="favorite-list" id="public-profile-events">
            @foreach($events as $event)
                <div class="fav-item">
                    <a href="{{ route('event.show', $event->id) }}">{{ $event->title }}</a>
                </div>
            @endforeach
        </div>
        <x-load-more-button :paginator="$events" target="#public-profile-events" />
    @endif

    <hr>
    <h3>Забронированные обмены</h3>
    <div class="favorite-list" id="public-profile-booked-exchanges">
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
    <x-load-more-button :paginator="$bookedExchanges" target="#public-profile-booked-exchanges" />

    <hr>
    <h3>Клубы</h3>
    <div class="favorite-list" id="public-profile-clubs">
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
    <x-load-more-button :paginator="$joinedClubs" target="#public-profile-clubs" />

    <hr>
    <h3>Мероприятия клубов пользователя</h3>
    <div class="favorite-list" id="public-profile-club-events">
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
    <x-load-more-button :paginator="$clubEvents" target="#public-profile-club-events" />

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

    <div class="comments-list" data-comments-list id="public-profile-comments">
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
    <x-load-more-button :paginator="$comments" target="#public-profile-comments" />
</section>

@endsection
