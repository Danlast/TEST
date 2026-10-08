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

                <div class="filter-field">
                    <label for="article-sort">Сортировка</label>
                    <select id="article-sort" name="sort">
                        <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Новые</option>
                        <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>Старые</option>
                        <option value="best_week" {{ $sort === 'best_week' ? 'selected' : '' }}>Лучшие за неделю</option>
                        <option value="best_month" {{ $sort === 'best_month' ? 'selected' : '' }}>Лучшие за месяц</option>
                        <option value="best_year" {{ $sort === 'best_year' ? 'selected' : '' }}>Лучшие за год</option>
                        <option value="best_all" {{ $sort === 'best_all' ? 'selected' : '' }}>Лучшие за всё время</option>
                    </select>
                </div>

                <div class="filter-wide">
                    <x-tag-picker :available-tags="$availableTags" :selected-tags="$selectedTags ?? []" />
                </div>
            </div>

            <div class="filter-actions">
                <button class="btn btn-primary">Найти статьи</button>
                @if(($query ?? '') !== '' || !empty($selectedTags ?? []) || $sort !== 'newest')
                    <a href="{{ route('articles.index') }}" class="btn btn-outline">Сбросить фильтры</a>
                @endif
            </div>
        </form>

        <div class="article-list" id="article-index-list">
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
                            <div class="article-card-meta-trailing">
                                <span class="article-card-view-count" aria-label="Просмотры: {{ $article->views_count }}" title="Просмотры">
                                    <span aria-hidden="true">&#x1F441;</span> {{ $article->views_count }}
                                </span>
                                <time datetime="{{ $article->created_at->toDateString() }}">{{ $article->created_at->format('d.m.Y') }}</time>
                            </div>
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
                        @if(filled($article->description))
                            <p class="article-card-description">{{ Str::limit($article->description, 180) }}</p>
                        @endif
                        <div class="article-card-engagement">
                            <span aria-label="Лайки: {{ $article->likes_count }}" title="Лайки"><span aria-hidden="true">&#x2661;</span> {{ $article->likes_count }}</span>
                            <span aria-label="Комментарии: {{ $article->comments_count }}" title="Комментарии"><span aria-hidden="true">&#x1F4AC;</span> {{ $article->comments_count }}</span>
                        </div>
                        <a href="{{ route('articles.show', $article) }}" class="btn btn-outline article-card-read-more">Продолжить чтение</a>
                    </div>
                </article>
            @empty
                <p>Пока нет опубликованных статей.</p>
            @endforelse
        </div>
        <x-load-more-button :paginator="$articles" target="#article-index-list" />
    </section>
@endsection
