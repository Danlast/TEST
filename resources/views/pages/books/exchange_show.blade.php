@extends('template.app')

@section('page')
<section class="content-shell exchange-page">
    <h2>Объявление</h2>

    <div class="detail-card">
        <div class="detail-content">
            <h3>{{ $exchange->title }}</h3>
            <p><strong>Описание:</strong> {{ $exchange->description }}</p>
            <p><strong>Место:</strong> {{ $exchange->place }}</p>
            <p><strong>Дата:</strong> {{ $exchange->date }}</p>
            <p><strong>Контакты:</strong> {{ $exchange->contacts }}</p>
            <p><strong>Статус:</strong> {{ $exchange->status === 'booked' ? 'Забронировано' : 'Активно' }}</p>

            @if($exchange->latitude && $exchange->longitude)
                <div class="section-top-sm">
                    <h4 class="map-heading">Карта</h4>
                    <div id="exchange-detail-map" class="map-frame map-frame-sm"></div>
                </div>
            @endif

            @auth
                @php
                    $currentUserId = Auth::id();
                    $isOwner = (int) $currentUserId === (int) $exchange->user_id;
                    $canBook = ! $isOwner && $exchange->status !== 'booked';
                @endphp

                @if(! $isOwner)
                    @if($exchange->status !== 'booked')
                        <form method="POST" action="{{ route('exchange.book', $exchange->id) }}" class="section-top-sm" onsubmit="return confirm('Подтвердить бронь этого обмена?');">
                            @csrf
                            <button class="btn btn-primary">Забронировать</button>
                        </form>
                    @elseif($exchange->booked_by_user_id === $currentUserId)
                        <form method="POST" action="{{ route('exchange.unbook', $exchange->id) }}" class="section-top-sm">
                            @csrf
                            <button class="btn btn-outline">Отказаться от обмена</button>
                        </form>
                    @else
                        <p class="status-error section-top-sm">Книга уже забронирована.</p>
                    @endif
                @else
                    <p class="status-muted section-top-sm">Это ваше объявление, бронирование недоступно.</p>
                @endif

                @if(auth()->user()->canManageBookExchange($exchange))
                    <div class="flex section-top-sm">
                        <a href="{{ route('exchange.edit', $exchange->id) }}" class="btn btn-edit">Редактировать</a>
                        <a href="{{ route('exchange.delete', $exchange->id) }}" class="btn btn-delete">Удалить</a>
                    </div>
                @endif
            @endauth
        </div>
    </div>
</section>

@if($exchange->latitude && $exchange->longitude)
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const lat = {{ (float) $exchange->latitude }};
            const lng = {{ (float) $exchange->longitude }};
            const map = L.map('exchange-detail-map', { attributionControl: false }).setView([lat, lng], 15);
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors'
            }).addTo(map);
            L.control.attribution({ prefix: false }).addTo(map);

            const markerIcon = L.divIcon({
                className: 'custom-marker',
                html: '📍',
                iconSize: [42, 42],
                iconAnchor: [21, 42],
                popupAnchor: [0, -42]
            });

            L.marker([lat, lng], { icon: markerIcon }).addTo(map)
                .bindPopup('{{ addslashes($exchange->place) }}')
                .openPopup();
        });
    </script>
@endif
@endsection
