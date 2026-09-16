@extends('template.app')

@section('page')
<section class="form-card">
    <h2>Новое объявление</h2>
    <form method="POST" action="{{ route('exchange.store') }}">
        @csrf

        <div class="form-group">
            <label>Название книги</label>
            <input type="text" name="title" placeholder="Введите название" value="{{ old('title') }}" required>
            @error('title') <span class="error">* {{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label>Описание</label>
            <textarea name="description" rows="3">{{ old('description') }}</textarea>
            @error('description') <span class="error">* {{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label>Место проведения</label>
            <div class="map-field">
                <input type="text" name="place" id="place-input" placeholder="Введите адрес или выберите на карте" value="{{ old('place') }}" required>
                <div id="map" class="form-map form-map-create"></div>
                <small class="form-help">Кликните по карте, чтобы указать точное место, или воспользуйтесь поиском адреса</small>
            </div>
            @error('place') <span class="error">* {{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label>Дата и время</label>
            <input type="datetime-local" name="date" value="{{ old('date') }}">
            @error('date') <span class="error">* {{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label>Контакты</label>
            <input type="text" name="contacts" placeholder="Телефон, Telegram, почта" value="{{ old('contacts') }}" required>
            @error('contacts') <span class="error">* {{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}">
            <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}">
            @error('latitude') <span class="error">* {{ $message }}</span> @enderror
            @error('longitude') <span class="error">* {{ $message }}</span> @enderror
        </div>

        <input type="submit" class="btn btn-primary" value="Создать">
    </form>
</section>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const defaultLat = {{ old('latitude') ?? 55.751244 }};
    const defaultLng = {{ old('longitude') ?? 37.618423 }};
    const map = L.map('map').setView([defaultLat, defaultLng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 19
    }).addTo(map);

    const markerIcon = L.divIcon({
        className: 'custom-marker',
        html: '🕮',
        iconSize: [42, 42],
        iconAnchor: [21, 42],
        popupAnchor: [0, -42]
    });

    const marker = L.marker([defaultLat, defaultLng], { icon: markerIcon, draggable: true }).addTo(map);
    const placeInput = document.getElementById('place-input');
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');

    function updateCoordinates(lat, lng, updatePlace = true) {
        lat = parseFloat(lat).toFixed(7);
        lng = parseFloat(lng).toFixed(7);
        latInput.value = lat;
        lngInput.value = lng;

        if (updatePlace) {
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.display_name) {
                        placeInput.value = data.display_name;
                    }
                })
                .catch(() => {
                    placeInput.value = `${lat}, ${lng}`;
                });
        }
    }

    map.on('click', function (e) {
        marker.setLatLng(e.latlng);
        updateCoordinates(e.latlng.lat, e.latlng.lng);
    });

    marker.on('dragend', function () {
        const position = marker.getLatLng();
        updateCoordinates(position.lat, position.lng);
    });

    let searchTimeout;
    placeInput.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        const query = placeInput.value.trim();
        if (query.length < 3) return;

        searchTimeout = setTimeout(() => {
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.length > 0) {
                        const first = data[0];
                        const lat = parseFloat(first.lat);
                        const lng = parseFloat(first.lon);
                        marker.setLatLng([lat, lng]);
                        map.setView([lat, lng], 15);
                        updateCoordinates(lat, lng, false);
                        placeInput.value = first.display_name;
                    }
                })
                .catch(() => {});
        }, 800);
    });
});
</script>
@endsection
