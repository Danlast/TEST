<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id User who created the article.
 * @property int|null $club_id Club that owns the publication, when applicable.
 * @property string $title
 * @property string|null $content
 * @property string|null $description
 * @property array<string>|null $tags
 * @property bool $is_published
 */
class Article extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'tags' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function author()
    {
        return $this->user();
    }

    public function club()
    {
        return $this->belongsTo(User::class, 'club_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function canBeManagedBy(User $user): bool
    {
        if ($user->role?->isStaff()) {
            return true;
        }

        if ($user->role?->managesClubContent()) {
            return $this->club_id && (int) $this->club_id === (int) ($user->club_id ?? $user->id);
        }

        return $user->id === $this->user_id;
    }
}
