@extends('template.app')

@section('page')
<div class="content-shell">
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

            <x-tag-picker :available-tags="$availableTags ?? []" :selected-tags="$tags ?? []" />

            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Найти мероприятия</button>
                @if(($query ?? '') !== '' || !empty($dateFrom) || !empty($dateTo) || !empty($tags ?? []))
                    <a href="{{ route('event.index') }}" class="btn btn-outline">Сбросить фильтры</a>
                @endif
            </div>
        </form>

        <div class="object-grid">
            @forelse($events as $event)
                <x-event-card :event="$event" />
            @empty
                <p>Мероприятий не найдено.</p>
            @endforelse
        </div>
        {{ $events->links() }}
</div>
@endsection
