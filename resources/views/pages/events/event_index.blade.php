@extends('template.app')

@section('page')
<div class="container page-container">
    <div class="page-shell">
        <div class="page-header">
            <div>
                <h2 class="section-title page-title">Все мероприятия</h2>
                <p class="page-subtitle">Ищите события по дате, тегам и ключевому слову в одном удобном списке.</p>
            </div>
        </div>

        <form method="GET" class="filter-panel">
            <div class="filter-grid">
                <div class="filter-field search-field">
                    <label for="event-index-search">Поиск мероприятия</label>
                    <input type="search" id="event-index-search" name="q" value="{{ $query ?? '' }}" placeholder="Название, место или тег" autocomplete="off">
                </div>

                <div class="filter-field">
                    <label>От</label>
                    <input type="date" name="date_from" value="{{ $dateFrom ?? '' }}">
                </div>

                <div class="filter-field">
                    <label>До</label>
                    <input type="date" name="date_to" value="{{ $dateTo ?? '' }}">
                </div>

                <div class="filter-field">
                    <label>Сортировка</label>
                    <select name="sort">
                        <option value="date" {{ ($sort ?? 'date') === 'date' ? 'selected' : '' }}>По дате</option>
                        <option value="popular" {{ ($sort ?? '') === 'popular' ? 'selected' : '' }}>По популярности</option>
                    </select>
                </div>
            </div>

            <fieldset class="filter-field tag-filter">
                <legend>Теги <span class="tag-count" data-tag-count>Выбрано: {{ count($tags ?? []) }}</span></legend>
                <div class="tag-options">
                    @foreach($availableTags ?? [] as $value => $label)
                        <label class="tag-option">
                            <input type="checkbox" name="tags[]" value="{{ $value }}" {{ in_array($value, $tags ?? [], true) ? 'checked' : '' }}>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Найти мероприятия</button>
                @if(($query ?? '') !== '' || !empty($dateFrom) || !empty($dateTo) || !empty($tags ?? []))
                    <a href="{{ route('event.index') }}" class="btn btn-outline">Сбросить фильтры</a>
                @endif
            </div>
        </form>

        <div class="object-grid">
            @forelse($events as $event)
                <div class="card">
                    <div class="card-img">
                        <img src="{{ $event->image_url }}" alt="{{ $event->title }}" height="100%" width="100%">
                    </div>
                    <div class="card-content">
                        <h3 class="card-title">{{ $event->title }}</h3>
                        <p>Дата: {{ $event->date }} · {{ $event->place }}</p>
                        @if(!empty($event->tags_labels))
                            <p>Теги: {{ implode(', ', $event->tags_labels) }}</p>
                        @endif
                        <p>Записалось: {{ $event->registered_count }}</p>
                        <a href="{{ route('event.show', $event->id) }}" class="btn btn-outline">Подробнее</a>
                    </div>
                </div>
            @empty
                <p>Мероприятий не найдено.</p>
            @endforelse
        </div>
        {{ $events->links() }}
    </div>
</div>
@endsection
