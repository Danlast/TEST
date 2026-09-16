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
                <div class="card">
                    <div class="card-content">
                        <h3 class="card-title">{{ $exchange->title }}</h3>
                        <p>{{ Str::limit($exchange->description, 120) }}</p>
                        <p><strong>Место:</strong> {{ $exchange->place }}</p>
                        <p><strong>Статус:</strong> {{ $exchange->status === 'booked' ? 'Забронировано' : 'Активно' }}</p>
                        <a href="{{ route('exchange.show', $exchange->id) }}" class="btn btn-outline">Подробнее</a>
                    </div>
                </div>
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
    const map = L.map('exchange-map').setView([55.751244, 37.618423], 10);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 19
    }).addTo(map);

    const markers = @json($exchangeMarkers);
    const markerIcon = L.divIcon({
        className: 'custom-marker',
        html: '📍',
        iconSize: [42, 42],
        iconAnchor: [21, 42],
        popupAnchor: [0, -42]
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
