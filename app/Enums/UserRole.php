<?php

namespace App\Enums;

enum UserRole: string
{
    case USER = 'user';
    case ADMIN = 'admin';
    case MODERATOR = 'moderator';
    case CLUB = 'club';
    case CLUB_MODERATOR = 'club_moderator';
    case BAN = 'ban';

    public function isStaff(): bool
    {
        return in_array($this, [self::ADMIN, self::MODERATOR], true);
    }

    public function managesClubContent(): bool
    {
        return in_array($this, [self::CLUB, self::CLUB_MODERATOR], true);
    }
}