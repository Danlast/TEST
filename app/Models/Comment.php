<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $event_id
 * @property int|null $profile_user_id
 * @property int|null $article_id
 * @property int|null $parent_id
 * @property int|null $reply_to_id
 * @property string $content
 */
class Comment extends Model
{
    public const DELETED_CONTENT = 'Комментарий был удален';

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function profileUser()
    {
        return $this->belongsTo(User::class, 'profile_user_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function repliedTo()
    {
        return $this->belongsTo(self::class, 'reply_to_id');
    }

    public function replyReferences()
    {
        return $this->hasMany(self::class, 'reply_to_id');
    }

    public function replies()
    {
        return $this->hasMany(self::class, 'parent_id')->oldest();
    }

    public static function paginateThreads($query, int $perPage, string $pageName): LengthAwarePaginator
    {
        $threads = $query
            ->whereNull('parent_id')
            ->oldest()
            ->paginate($perPage, ['*'], $pageName)
            ->appends(request()->query());

        $comments = $threads->getCollection();
        $parentIds = $comments->modelKeys();

        while ($parentIds !== []) {
            $replies = collect();
            self::query()
                ->with(['user', 'repliedTo.user'])
                ->whereIn('parent_id', $parentIds)
                ->oldest()
                ->chunkById(100, function ($batch) use (&$replies) {
                    $replies = $replies->concat($batch);
                });

            if ($replies->isEmpty()) {
                break;
            }

            $comments = $comments->concat($replies);
            $parentIds = $replies->pluck('id')->all();
        }

        $comments->loadMissing(['user', 'repliedTo.user']);
        $threads->setCollection(self::nestReplies($comments));

        return $threads;
    }

    public static function nestReplies(Collection $comments): Collection
    {
        $commentsByParent = $comments->groupBy(fn (self $comment) => $comment->parent_id ?? 0);
        $buildTree = function (int $parentId) use (&$buildTree, $commentsByParent): Collection {
            return $commentsByParent->get($parentId, collect())->each(function (self $comment) use (&$buildTree) {
                $comment->setRelation('replies', $buildTree($comment->id));
            });
        };

        return $buildTree(0);
    }
}
