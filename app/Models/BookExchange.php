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

    public function canBeManagedBy(User $user): bool
    {
        if ($user->role?->isStaff()) {
            return true;
        }

        return $user->id === $this->user_id;
    }
}