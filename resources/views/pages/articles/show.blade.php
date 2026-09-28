@extends('template.app')

@section('page')
<section class="content-shell article-page">
    <div class="detail-card">
        <div class="detail-content">
            <h2>{{ $article->title }}</h2>
            <p><strong>Описание:</strong> {{ $article->description }}</p>
            <p>
                <strong>Автор:</strong>
                @if($article->club)
                    <a href="{{ route('user.profile', $article->club) }}">{{ $article->club->username }}</a>
                @elseif($article->user)
                    <a href="{{ route('user.profile', $article->user) }}">{{ $article->user->username }}</a>
                @else
                    Пользователь
                @endif
            </p>
            @if(!empty($article->tags))
                <p><strong>Теги:</strong> {{ implode(', ', $article->tags) }}</p>
            @endif
            <div class="content-prose">{{ $article->content }}</div>

            @auth
                @if($article->canBeManagedBy(auth()->user()))
                    <div class="flex section-top-lg">
                        <a href="{{ route('articles.edit', $article) }}" class="btn btn-edit">Редактировать</a>
                        <a href="{{ route('articles.delete', $article) }}" class="btn btn-delete">Удалить</a>
                    </div>
                @endif
            @endauth
        </div>
    </div>

    <div class="form-group section-top">
        <h3>Комментарии</h3>
        @auth
            <form method="POST" action="{{ route('articles.comment.store', $article) }}" class="comment-form">
                @csrf
                <textarea name="content" rows="3" placeholder="Напишите комментарий" required></textarea>
                <button class="btn btn-primary form-submit">Отправить</button>
            </form>
        @endauth

        @if($article->comments->isNotEmpty())
            <div class="comments-list">
                @foreach($article->comments()->with('user')->latest()->get() as $comment)
                    <x-comment-item :comment="$comment" />
                @endforeach
            </div>
        @else
            <p class="empty-hint">Комментариев пока нет.</p>
        @endif
    </div>
</section>
@endsection
