@extends('template.app')

@section('page')
<section class="form-card">
    <h2>Редактирование объявления</h2>
    <form method="POST" action="{{ route('exchange.update', $exchange->id) }}">
        @csrf

        <div class="form-group">
            <label>Название книги</label>
            <input type="text" name="title" value="{{ old('title', $exchange->title) }}" required>
            @error('title') <span class="error">* {{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label>Описание</label>
            <textarea name="description" rows="3">{{ old('description', $exchange->description) }}</textarea>
            @error('description') <span class="error">* {{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label>Место</label>
            <input type="text" name="place" value="{{ old('place', $exchange->place) }}" required>
            @error('place') <span class="error">* {{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label>Дата и время</label>
            <input type="datetime-local" name="date" value="{{ old('date', $exchange->date ? $exchange->date->format('Y-m-d\TH:i') : '') }}" required>
            @error('date') <span class="error">* {{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label>Контакты</label>
            <input type="text" name="contacts" value="{{ old('contacts', $exchange->contacts) }}" required>
            @error('contacts') <span class="error">* {{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label>Координаты</label>
            <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude', $exchange->latitude) }}">
            <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude', $exchange->longitude) }}">
            <div id="map" class="form-map form-map-edit"></div>
            @error('latitude') <span class="error">* {{ $message }}</span> @enderror
            @error('longitude') <span class="error">* {{ $message }}</span> @enderror
        </div>

        <input type="submit" class="btn btn-primary" value="Сохранить">
    </form>
</section>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const defaultLat = {{ old('latitude', $exchange->latitude ?? 55.751244) }};
    const defaultLng = {{ old('longitude', $exchange->longitude ?? 37.618423) }};
    const map = L.map('map').setView([defaultLat, defaultLng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 19
    }).addTo(map);

    const marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);

    map.on('click', function (e) {
        marker.setLatLng(e.latlng);
        document.getElementById('latitude').value = e.latlng.lat.toFixed(7);
        document.getElementById('longitude').value = e.latlng.lng.toFixed(7);
    });

    marker.on('dragend', function () {
        const position = marker.getLatLng();
        document.getElementById('latitude').value = position.lat.toFixed(7);
        document.getElementById('longitude').value = position.lng.toFixed(7);
    });
});
</script>
@endsection
