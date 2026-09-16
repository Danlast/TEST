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
        <p class="comment-content">{{ $comment->content }}</p>
    </div>
    @auth
        @if(auth()->user()->canDeleteComment($comment) || auth()->id() === $comment->user_id)
            <div class="comment-actions">
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
                        <form method="POST" action="{{ route('comments.destroy', $comment) }}" onsubmit="return confirm('Удалить комментарий?');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-delete">Удалить</button>
                        </form>
                    @endif
                </div>
            </div>
        @endif
    @endauth
</div>