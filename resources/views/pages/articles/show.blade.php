@extends('template.app')

@section('page')
<section class="content article-page">
    <div class="article-content">
        <div class="detail-content">
            <div class="article-detail-meta">
                @if($article->club)
                    <a href="{{ route('club.profile', $article->club) }}">{{ $article->club->username }}</a>
                @elseif($article->user)
                    <a href="{{ route('user.profile', $article->user) }}">{{ $article->user->username }}</a>
                @else
                    <span>Пользователь</span>
                @endif
                <div class="article-detail-meta-trailing">
                    <span class="article-card-view-count" aria-label="Просмотры: {{ $article->views_count }}" title="Просмотры">
                        <span aria-hidden="true">&#x1F441;</span> {{ $article->views_count }}
                    </span>
                    <time datetime="{{ $article->created_at->toDateString() }}">{{ $article->created_at->format('d.m.Y') }}</time>
                </div>
            </div>

            <div class="article-detail-title-row">
                <h2>{{ $article->title }}</h2>
                @auth
                    @if($article->canBeManagedBy(auth()->user()))
                        <div class="article-detail-actions">
                            <a href="{{ route('articles.edit', $article) }}" class="btn btn-edit">Редактировать</a>
                            <a href="{{ route('articles.delete', $article) }}" class="btn btn-delete">Удалить</a>
                        </div>
                    @endif
                @endauth
            </div>

            @if(!empty($article->tags))
                <p class="article-detail-tags">{{ implode(', ', $article->tags) }}</p>
            @endif
            <div class="content-prose">{{ $article->content }}</div>

            <div class="article-card-engagement" id="article-engagement" aria-label="Статистика статьи">
                <span aria-label="Лайки: {{ $article->likes_count }}" title="Лайки"><span aria-hidden="true">&#x2661;</span> {{ $article->likes_count }}</span>
                <span aria-label="Комментарии: {{ $article->comments_count }}" title="Комментарии"><span aria-hidden="true">&#x1F4AC;</span> {{ $article->comments_count }}</span>
                @auth
                    <form method="POST" action="{{ route('favorites.store', ['id' => $article->id, 'type' => 'article']) }}">
                        @csrf
                        <button type="submit" class="article-like-button {{ $likedByUser ? 'is-liked' : '' }}" aria-pressed="{{ $likedByUser ? 'true' : 'false' }}">
                            {{ $likedByUser ? 'Убрать лайк' : 'Нравится' }}
                        </button>
                    </form>
                @endauth
            </div>
        </div>
    </div>

    <div class="form-group section-top" data-comment-section>
        <h3>Комментарии</h3>
        @auth
            <form method="POST" action="{{ route('articles.comment.store', $article) }}" class="comment-form">
                @csrf
                <textarea name="content" rows="3" placeholder="Напишите комментарий" required></textarea>
                <button class="btn btn-primary form-submit">Отправить</button>
            </form>
        @endauth

        <div class="comments-list" data-comments-list id="article-comments">
            @if($comments->isNotEmpty())
            @foreach($comments as $comment)
                    <x-comment-item :comment="$comment" :depth="0" />
                @endforeach
            @endif
        </div>
        @if($comments->isEmpty())
            <p class="empty-hint">Комментариев пока нет.</p>
        @endif
        <x-load-more-button :paginator="$comments" target="#article-comments" />
    </div>
</section>
@endsection
