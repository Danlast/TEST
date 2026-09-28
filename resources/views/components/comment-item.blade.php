<div class="comment-thread" data-comment-id="{{ $comment->id }}" data-comment-depth="{{ $depth }}">
    <div class="comment-item">
        @php($commentAvatarUrl = $comment->user?->avatar_url)
        @if($commentAvatarUrl)
            <img src="{{ $commentAvatarUrl }}" alt="Аватар" class="comment-avatar">
        @else
            <div class="comment-avatar comment-avatar-fallback">
                {{ strtoupper(substr($comment->user->username ?? 'U', 0, 1)) }}
            </div>
        @endif
        <div class="comment-body">
            <div class="comment-header">
                <a href="{{ route('user.profile', $comment->user?->id) }}" class="comment-author">
                    {{ $comment->user->username ?? 'Пользователь' }}
                </a>
                <span class="comment-date">{{ $comment->created_at->setTimezone('Europe/Moscow')->translatedFormat('d.m.Y H:i') }}</span>
            </div>
            @if($comment->repliedTo)
                <blockquote class="comment-quote">
                    <strong>{{ $comment->repliedTo->user->username ?? 'Пользователь' }}</strong>
                    <span>{{ $comment->repliedTo->content }}</span>
                </blockquote>
            @endif
            <p class="comment-content {{ $comment->content === 'Комментарий был удален' ? 'comment-deleted' : '' }}">{{ $comment->content }}</p>
        </div>
        @auth
            <div class="comment-actions">
                @if($depth <= 5 && $comment->content !== 'Комментарий был удален')
                    <details class="comment-reply">
                        <summary>Ответить</summary>
                        <form method="POST" action="{{ route('comments.reply', $comment) }}" class="comment-reply-form">
                            @csrf
                            <textarea name="content" rows="2" maxlength="1000" placeholder="Ваш ответ" required></textarea>
                            <button type="submit" class="btn btn-primary">Отправить</button>
                        </form>
                    </details>
                @endif
                @if($comment->content !== 'Комментарий был удален' && (auth()->user()->canDeleteComment($comment) || auth()->id() === $comment->user_id))
                    <button type="button" class="comment-menu-toggle" aria-label="Действия с комментарием" onclick="var menu=this.parentNode.querySelector('.comment-menu'); menu.classList.toggle('is-open');">...</button>
                    <div class="comment-menu">
                        @if(auth()->id() === $comment->user_id)
                            <form method="POST" action="{{ route('comments.update', $comment) }}" class="comment-edit-form">
                                @csrf
                                @method('PUT')
                                <textarea name="content" rows="3">{{ $comment->content }}</textarea>
                                <button type="submit" class="btn btn-edit">Сохранить</button>
                            </form>
                        @endif
                        @if(auth()->user()->canDeleteComment($comment))
                            <form method="POST" action="{{ route('comments.destroy', $comment) }}" class="comment-delete-form" onsubmit="return confirm('Удалить комментарий?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-delete">Удалить</button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        @endauth
    </div>

    @if($comment->replies->isNotEmpty())
        <details class="comment-replies" open>
            <summary>Ответы ({{ $comment->replies->count() }})</summary>
            <div class="comment-replies-list">
                @foreach($comment->replies as $reply)
                    <x-comment-item :comment="$reply" :depth="$depth + 1" />
                @endforeach
            </div>
        </details>
    @endif
</div>