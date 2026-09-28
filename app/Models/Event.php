<?php

namespace App\Models;

use App\Enums\EventTag;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $formatted_date
 * @property string $short_place
 */
class Event extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'tags' => 'array',
    ];

    public function registrations()
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'event_registrations');
    }

    public function club()
    {
        return $this->belongsTo(User::class, 'club_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function getImagePathAttribute()
    {
        return $this->image ? ltrim($this->image, '/') : null;
    }

    public function getImageUrlAttribute()
    {
        return $this->image_path
            ? route('event.image', ['path' => $this->image_path])
            : asset('images/event-placeholder.svg');
    }

    public function getShortPlaceAttribute(): string
    {
        $parts = array_values(array_filter(array_map('trim', explode(',', (string) $this->place))));
        $parts = array_values(array_filter($parts, function (string $part) {
            return ! preg_match('/^\d{5,6}$/', $part)
                && ! preg_match('/\b(russia|россия|federal district|федеральный округ)\b/ui', $part);
        }));

        return implode(', ', array_slice($parts, 0, 5)) ?: (string) $this->place;
    }

    public function getFormattedDateAttribute(): string
    {
        return \Illuminate\Support\Carbon::parse($this->date)
            ->locale('ru')
            ->translatedFormat('j F Y, H:i');
    }

    public function getRegisteredCountAttribute()
    {
        if ($this->relationLoaded('registrations')) {
            return $this->registrations->count();
        }

        return array_key_exists('registrations_count', $this->attributes)
            ? (int) $this->attributes['registrations_count']
            : $this->registrations()->count();
    }

    public function getCapacityLabelAttribute()
    {
        return $this->registered_count . '/' . $this->max_entries;
    }

    public function getTagsLabelsAttribute(): array
    {
        $tags = (array) ($this->tags ?? []);

        return array_values(array_filter(array_map(function ($tag) {
            $enum = EventTag::tryFrom($tag);

            return $enum ? $enum->label() : null;
        }, $tags)));
    }
}
