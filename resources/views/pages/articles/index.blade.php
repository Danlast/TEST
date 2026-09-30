@extends('template.app')

@section('page')
<section class="content">
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

                <div class="filter-wide">
                    <x-tag-picker :available-tags="$availableTags" :selected-tags="$selectedTags ?? []" />
                </div>
            </div>

            <div class="filter-actions">
                <button class="btn btn-primary">Найти статьи</button>
                @if(($query ?? '') !== '' || !empty($selectedTags ?? []))
                    <a href="{{ route('articles.index') }}" class="btn btn-outline">Сбросить фильтры</a>
                @endif
            </div>
        </form>

        <div class="article-list">
            @forelse($articles as $article)
                <article class="card article-card">
                    <div class="card-content article-card-content">
                        <div class="article-card-meta">
                            @if($article->club)
                                <a href="{{ route('club.profile', $article->club) }}">{{ $article->club->username }}</a>
                            @elseif($article->user)
                                <a href="{{ route('user.profile', $article->user) }}">{{ $article->user->username }}</a>
                            @else
                                <span>Пользователь</span>
                            @endif
                            <time datetime="{{ $article->created_at->toDateString() }}">{{ $article->created_at->format('d.m.Y') }}</time>
                        </div>
                        <h3 class="card-title">
                            <a class="article-card-title-link" href="{{ route('articles.show', $article) }}">{{ $article->title }}</a>
                        </h3>
                        @if(!empty($article->tags))
                            <p class="article-card-tags">{{ implode(', ', $article->tags) }}</p>
                        @endif
                        @if($article->banner_url)
                            <img class="article-card-banner" src="{{ $article->banner_url }}" alt="Баннер статьи: {{ $article->title }}">
                        @endif
                        <a href="{{ route('articles.show', $article) }}" class="btn btn-outline article-card-read-more">Продолжить чтение</a>
                    </div>
                </article>
            @empty
                <p>Пока нет опубликованных статей.</p>
            @endforelse
        </div>
        {{ $articles->links() }}
    </section>
@endsection
