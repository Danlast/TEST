@extends('template.app')
@section('page')

<div class="detail-card">
    <div class="detail-img">
        <img src="{{ $event->image_url }}" alt="" height="100%" width="100%">
    </div>
    <div class="detail-content">
        <h2>{{ $event->title}}</h2>
        <p><strong>Описание:</strong> {{ $event->description}}</p>
        @if(!empty($event->tags_labels))
            <p><strong>Теги:</strong> {{ implode(', ', $event->tags_labels) }}</p>
        @endif
        <p><strong>Дата:</strong> {{ $event->date}}</p>
        <p><strong>Место:</strong> {{ $event->place}}</p>
        <p><strong>Минимум для проведения:</strong> {{ $event->min_entries ?? 0 }}</p>
        <p><strong>Записались:</strong> {{ $event->registered_count }}/{{ $event->max_entries }}</p>

        @guest
            <p>Чтобы записаться на мероприятие, пожалуйста, <a href="{{ route('show.login') }}">войдите</a></p>
        @endguest

        @auth
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
        @endauth

        <div class="form-group">
            <h3>Список записавшихся</h3>
            @if($event->registrations->isNotEmpty())
                <ul>
                    @foreach($event->registrations as $registration)
                        <li>
                            <a href="{{ route('user.profile', $registration->user->id) }}">
                                {{ $registration->user->username ?? $registration->user->email }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @else
                <p>Пока никто не записался.</p>
            @endif
        </div>

        <div class="form-group section-top">
            <h3>Комментарии</h3>
            @auth
                <form method="POST" action="{{ route('comments.event.store', $event) }}" class="comment-form">
                    @csrf
                    <textarea name="content" rows="3" placeholder="Напишите комментарий" required></textarea>
                    <button class="btn btn-primary form-submit">Отправить</button>
                </form>
            @endauth

            @if($event->comments->isNotEmpty())
                <div class="comments-list">
                    @foreach($event->comments()->with('user')->latest()->get() as $comment)
                        <x-comment-item :comment="$comment" />
                    @endforeach
                </div>
            @else
                <p class="empty-hint">Комментариев пока нет.</p>
            @endif
        </div>
    </div>
</div>

@endsection