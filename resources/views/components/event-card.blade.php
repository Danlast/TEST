<article class="card event-card" data-event-id="{{ $event->id }}">
    <div class="card-img event-card-poster">
        <img src="{{ $event->image_url }}" alt="Афиша мероприятия {{ $event->title }}">
    </div>
    <div class="card-content event-card-content">
        <h3 class="card-title">{{ $event->title }}</h3>

        @if(!empty($event->tags_labels))
            <p class="event-card-tags">{{ implode(', ', $event->tags_labels) }}</p>
        @endif

        @if(!empty($event->description))
            <p class="event-card-description">{{ $event->description }}</p>
        @endif

        <p class="event-card-place">{{ $event->short_place }}</p>

        <div class="event-card-club-date">
            @if($event->club)
                <a href="{{ route('club.profile', $event->club) }}" class="event-card-club">{{ $event->club->username }}</a>
            @else
                <span></span>
            @endif
            <time datetime="{{ $event->date }}">{{ $event->formatted_date }}</time>
        </div>

        <p class="event-card-registrations">Записалось: {{ $event->registered_count }}/{{ $event->max_entries }}</p>
        <a href="{{ route('event.show', $event->id) }}" class="btn btn-outline event-card-link">Подробнее</a>
    </div>
</article>