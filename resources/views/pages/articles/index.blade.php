@extends('template.app')

@section('page')
<div class="container page-container">
    <div class="page-shell">
        <div class="page-header">
            <div>
                <h2 class="section-title page-title">Статьи</h2>
                <p class="page-subtitle">Читайте полезные материалы, находите по тегам и открывайте новые темы.</p>
            </div>
            @auth
                <a href="{{ route('articles.create') }}" class="btn btn-primary">Написать статью</a>
            @endauth
        </div>

        <form method="GET" action="{{ route('articles.index') }}" class="filter-panel">
            <div class="filter-grid">
                <div class="filter-field search-field">
                    <label for="article-search">Поиск статьи</label>
                    <input type="search" id="article-search" name="q" value="{{ $query ?? '' }}" placeholder="Заголовок, описание или тег" autocomplete="off">
                </div>

                <fieldset class="filter-field tag-filter filter-wide">
                    <legend>Теги <span class="tag-count" data-tag-count>Выбрано: {{ count($selectedTags ?? []) }}</span></legend>
                    <div class="tag-options">
                        @foreach($availableTags as $value => $label)
                            <label class="tag-option">
                                <input type="checkbox" name="tags[]" value="{{ $value }}" {{ in_array($value, $selectedTags ?? [], true) ? 'checked' : '' }}>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            </div>

            <div class="filter-actions">
                <button class="btn btn-primary">Найти статьи</button>
                @if(($query ?? '') !== '' || !empty($selectedTags ?? []))
                    <a href="{{ route('articles.index') }}" class="btn btn-outline">Сбросить фильтры</a>
                @endif
            </div>
        </form>

        <div class="object-grid">
            @forelse($articles as $article)
                <div class="card">
                    <div class="card-content">
                        <h3 class="card-title">{{ $article->title }}</h3>
                        <p>{{ Str::limit($article->description ?: $article->content, 140) }}</p>
                        @if(!empty($article->tags))
                            <p><strong>Теги:</strong> {{ implode(', ', $article->tags) }}</p>
                        @endif
                        <p><strong>Автор:</strong> {{ $article->club->username ?? $article->user->username ?? 'Пользователь' }}</p>
                        <a href="{{ route('articles.show', $article) }}" class="btn btn-outline">Читать</a>
                    </div>
                </div>
            @empty
                <p>Пока нет опубликованных статей.</p>
            @endforelse
        </div>
        {{ $articles->links() }}
    </div>
</div>
@endsection
