@extends('template.app')

@section('page')
<div class="container page-container">
    <div class="page-shell">
        <div class="page-header">
            <div>
                <h2 class="section-title page-title">Обмен книгами</h2>
                <p class="page-subtitle">Ищите активные объявления и смотрите их на карте.</p>
            </div>
            <a href="{{ route('exchange.create') }}" class="btn btn-primary">Выложить объявление</a>
        </div>
        <div class="object-grid">
            @forelse($exchanges as $exchange)
                <article class="card exchange-card">
                    <div class="card-content exchange-card-content">
                        <h3 class="card-title">{{ $exchange->title }}</h3>
                        <span class="exchange-status {{ $exchange->status === 'booked' ? 'is-booked' : 'is-active' }}">
                            {{ $exchange->status === 'booked' ? 'Забронировано' : 'Активно' }}
                        </span>
                        @if(!empty($exchange->description))
                            <p class="exchange-card-description">{{ $exchange->description }}</p>
                        @endif
                        <p class="exchange-card-place">{{ $exchange->short_place }}</p>
                        <div class="exchange-card-author-date">
                            @if($exchange->user)
                                <a href="{{ route('user.profile', $exchange->user->id) }}">{{ $exchange->user->username }}</a>
                            @else
                                <span></span>
                            @endif
                            <time datetime="{{ $exchange->date?->toIso8601String() }}">{{ $exchange->formatted_date }}</time>
                        </div>
                        <a href="{{ route('exchange.show', $exchange->id) }}" class="btn btn-outline exchange-card-link">Подробнее</a>
                    </div>
                </article>
            @empty
                <p>Пока нет объявлений.</p>
            @endforelse
        </div>

        {{ $exchanges->links() }}

        <div class="container-map section-spaced">
            <div class="map-section">
                <h3 class="section-title section-title-spaced">Карта объявлений</h3>
                <div id="exchange-map" class="map-container"></div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@php
$exchangeMarkers = $exchangeMarkers
    ->map(function ($exchange) {
        return [
            'id' => $exchange->id,
            'title' => $exchange->title,
            'latitude' => (float) $exchange->latitude,
            'longitude' => (float) $exchange->longitude,
            'place' => $exchange->place,
            'url' => route('exchange.show', $exchange->id),
        ];
    })
    ->values();
@endphp
<script>
document.addEventListener('DOMContentLoaded', function () {
    const map = L.map('exchange-map', { attributionControl: false }).setView([55.751244, 37.618423], 10);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors'
    }).addTo(map);
    L.control.attribution({ prefix: false }).addTo(map);

    const markers = @json($exchangeMarkers);
    const markerIcon = L.divIcon({
        className: 'custom-marker',
        html: '<img src="{{ asset('images/event-placeholder.svg') }}" alt="">',
        iconSize: [26, 32],
        iconAnchor: [13, 32],
        popupAnchor: [0, -30]
    });

    const markerGroup = [];
    markers.forEach((item) => {
        const marker = L.marker([item.latitude, item.longitude], { icon: markerIcon }).addTo(map);
        marker.bindPopup(`
            <div class="map-popup">
                <strong>${item.title}</strong><br>
                ${item.place}<br>
                <a href="${item.url}" class="map-popup-link">Подробнее</a>
            </div>
        `);
        markerGroup.push(marker);
    });

    if (markerGroup.length > 0) {
        const group = new L.featureGroup(markerGroup);
        map.fitBounds(group.getBounds().pad(0.1));
    }
});
</script>
@endsection
