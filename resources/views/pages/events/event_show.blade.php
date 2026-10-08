@extends('template.app')
@section('page')

<section class="content event-detail-card">
    <div class="detail-img">
        <img src="{{ $event->image_url }}" alt="" height="100%" width="100%">
    </div>
    <div class="detail-content event-detail-content">
        <h2>{{ $event->title }}</h2>
        @if(!empty($event->tags_labels))
            <p class="event-detail-tags">{{ implode(', ', $event->tags_labels) }}</p>
        @endif
        @if(!empty($event->description))
            <p class="event-detail-description">{{ $event->description }}</p>
        @endif
        <p class="event-detail-place"><strong>Место:</strong> {{ $event->short_place }}</p>
        <div class="event-detail-meta">
            @if($event->club)
                <span><strong>Клуб:</strong> <a href="{{ route('club.profile', $event->club) }}">{{ $event->club->username }}</a></span>
            @endif
            <time datetime="{{ $event->date }}"><strong>Дата:</strong> {{ $event->formatted_date }}</time>
        </div>
        <p class="event-detail-capacity">Записано: {{ $event->registered_count }}/{{ $event->max_entries }}</p>

        @guest
            <p>Чтобы записаться на мероприятие, пожалуйста, <a href="{{ route('show.login') }}">войдите</a></p>
        @endguest

        @auth
        @if($isBannedFromClub)
            <p class="status-muted">Вы были забанены в данном клубе и не можете участвовать в его мероприятиях.</p>
        @else
            <div class="flex">
                @if(auth()->user()->hasRegistered($event->id))
                    <form action="{{ route('favorites.destroy', $event->id) }}" method="POST" onsubmit="return confirm('Отменить запись?');">
                        @csrf
                        <input class="btn btn-delete" type="submit" value="Отменить запись">
                    </form>
                @elseif($event->registered_count < $event->max_entries)
                    <form action="{{ route('favorites.store', $event->id) }}" method="POST" onsubmit="return confirm('Записаться на мероприятие?');">
                        @csrf
                        <input class="btn btn-primary" type="submit" value="Записаться">
                    </form>
                @else
                    <button class="btn btn-delete" disabled>Мест нет</button>
                @endif

                @if(auth()->user()->canManageEvent($event))
                    <a href="{{ route('event.edit', $event->id) }}" class="btn btn-edit">Редактировать</a>
                    <a href="{{ route('event.delete', $event->id) }}" class="btn btn-delete">Удалить</a>
                @endif
            </div>
        @endif
        @endauth

        <div class="form-group event-attendees-section">
            <h3>Список записавшихся</h3>
            @if($registrations->isNotEmpty())
                <ul class="event-attendee-list" id="event-attendees">
                    @foreach($registrations as $registration)
                        <li>
                            <a href="{{ route('user.profile', $registration->user->id) }}">
                                {{ $registration->user->username ?? $registration->user->email }}
                            </a>
                            @auth
                                @if(auth()->user()->canManageEvent($event) && auth()->id() !== $registration->user_id)
                                    <details class="event-registration-actions">
                                        <summary aria-label="Управление записью пользователя">Действия</summary>
                                        <form action="{{ route('favorites.destroy', $event->id) }}" method="POST" class="event-registration-remove-form">
                                            @csrf
                                            <input type="hidden" name="user_id" value="{{ $registration->user_id }}">
                                            <button type="submit" class="btn btn-delete" onclick="return confirm('Удалить запись пользователя?');">Удалить запись</button>
                                        </form>
                                    </details>
                                @endif
                            @endauth
                        </li>
                    @endforeach
                </ul>
                <x-load-more-button :paginator="$registrations" target="#event-attendees" />
            @else
                <p>Пока никто не записался.</p>
            @endif
        </div>

        <div class="form-group section-top" data-comment-section>
            <h3>Комментарии</h3>
            @auth
                @if($isBannedFromClub)
                    <p class="status-muted">Вы были забанены в данном клубе и не можете комментировать его мероприятия.</p>
                @else
                    <form method="POST" action="{{ route('comments.event.store', $event) }}" class="comment-form">
                        @csrf
                        <textarea name="content" rows="3" placeholder="Напишите комментарий" required></textarea>
                        <button class="btn btn-primary form-submit">Отправить</button>
                    </form>
                @endif
            @endif

            <div class="comments-list" data-comments-list id="event-comments">
                @if($comments->isNotEmpty())
                    @foreach($comments as $comment)
                        <x-comment-item :comment="$comment" :depth="0" :allow-replies="!$isBannedFromClub" />
                    @endforeach
                @endif
            </div>
            @if($comments->isEmpty())
                <p class="empty-hint">Комментариев пока нет.</p>
            @endif
            <x-load-more-button :paginator="$comments" target="#event-comments" />
        </div>
    </div>
</section>

@endsection