<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $username
 * @property string $email
 * @property \App\Enums\UserRole $role
 * @property int|null $club_id
 * @property bool $club_banned
 * @property int|null $club_ban_club_id
 * @property string|null $club_ban_reason
 * @property string|null $description
 * @property string|null $avatar
 * @property bool $is_profile_private
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'role',
        'description',
        'avatar',
        'is_profile_private',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'role' => \App\Enums\UserRole::class,
        'password' => 'hashed',
        'is_profile_private' => 'boolean',
    ];

    public function registrations()
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function hasRegistered($eventId)
    {
        return $this->registrations()->where('event_id', $eventId)->exists();
    }

    public function registeredEvents()
    {
        return $this->belongsToMany(Event::class, 'event_registrations');
    }

    public function clubMemberships()
    {
        return $this->hasMany(ClubMembership::class);
    }

    public function joinedClubs()
    {
        return $this->belongsToMany(User::class, 'club_memberships', 'user_id', 'club_id');
    }

    public function club()
    {
        return $this->belongsTo(User::class, 'club_id');
    }

    public function clubEvents()
    {
        return Event::query()
            ->whereIn('club_id', $this->joinedClubs()->pluck('users.id'))
            ->whereNotIn('id', $this->registeredEvents()->pluck('events.id'));
    }

    public function canAccessClubContent($clubId): bool
    {
        if (! $clubId) {
            return true;
        }

        if (! $this->club_banned) {
            return true;
        }

        return ! ($this->club_ban_club_id && (int) $this->club_ban_club_id === (int) $clubId);
    }

    public function isBannedFromClub(int $clubId): bool
    {
        return (bool) $this->club_banned
            && (int) $this->club_ban_club_id === $clubId;
    }

    public function canViewProfile(?User $viewer): bool
    {
        return ! $this->is_profile_private || ($viewer && $viewer->is($this));
    }

    public function canParticipateInClubEvent($event): bool
    {
        return $this->canAccessClubContent($event->club_id);
    }

    public function canManageEvent($event): bool
    {
        $role = $this->role;

        if ($role?->isStaff()) {
            return true;
        }

        if ($role === \App\Enums\UserRole::CLUB) {
            return $event->club_id && (int) $event->club_id === (int) $this->id;
        }

        if ($role === \App\Enums\UserRole::CLUB_MODERATOR) {
            return $event->club_id && (int) $event->club_id === (int) $this->club_id;
        }

        return false;
    }

    public function canCreateEvents(): bool
    {
        return $this->role?->isStaff() || $this->role?->managesClubContent() ?? false;
    }

    public function canManageBookExchange($exchange): bool
    {
        if ($this->role?->isStaff()) {
            return true;
        }

        return $this->id === $exchange->user_id;
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function profileComments()
    {
        return $this->hasMany(Comment::class, 'profile_user_id');
    }

    public function getAvatarPathAttribute()
    {
        return $this->avatar ? ltrim($this->avatar, '/') : null;
    }

    public function getAvatarUrlAttribute()
    {
        return $this->avatar_path ? route('profile.avatar', ['path' => $this->avatar_path]) : null;
    }

    public function canDeleteComment($comment): bool
    {
        if ($this->role?->isStaff()) {
            return true;
        }

        if ($this->id === $comment->user_id) {
            return true;
        }

        if ($comment->event_id && $comment->event && $this->canManageEvent($comment->event)) {
            return true;
        }

        if ($comment->profile_user_id && $comment->profileUser) {
            if ($comment->profile_user_id === $this->id) {
                return true;
            }

            if ($this->canManageClub($comment->profileUser)) {
                return true;
            }
        }

        return false;
    }

    public function canManageClub($club): bool
    {
        if ($this->role === \App\Enums\UserRole::ADMIN) {
            return true;
        }

        if ($this->role === \App\Enums\UserRole::CLUB && $this->id === $club->id) {
            return true;
        }

        return $this->role === \App\Enums\UserRole::CLUB_MODERATOR
            && $this->club_id
            && (int) $this->club_id === (int) $club->id;
    }

}
