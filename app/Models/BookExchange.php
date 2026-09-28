<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string|null $description
 * @property string $place
 * @property \Illuminate\Support\Carbon|null $date
 * @property string $contacts
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string $status
 * @property int|null $booked_by_user_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $short_place
 * @property string $formatted_date
 */
class BookExchange extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'date' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookedByUser()
    {
        return $this->belongsTo(User::class, 'booked_by_user_id');
    }

    public function getShortPlaceAttribute(): string
    {
        $parts = array_values(array_filter(array_map('trim', explode(',', $this->place))));
        $parts = array_values(array_filter($parts, function (string $part) {
            return ! preg_match('/^\d{5,6}$/', $part)
                && ! preg_match('/\b(russia|россия|federal district|федеральный округ)\b/ui', $part);
        }));

        return implode(', ', array_slice($parts, 0, 5)) ?: $this->place;
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->date
            ? $this->date->locale('ru')->translatedFormat('j F Y, H:i')
            : 'Дата не указана';
    }

    public function canBeManagedBy(User $user): bool
    {
        if ($user->role?->isStaff()) {
            return true;
        }

        return $user->id === $this->user_id;
    }
}