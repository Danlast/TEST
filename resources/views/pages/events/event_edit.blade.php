@extends('template.app')
@section('page')

<!-- ========== ИЗМЕНЕНИ МЕРОПРИЯТИЯ ========== -->
<section class="content-shell form-card">
    <h2>Изменить мероприятие</h2>
    <form id="event-edit-form" method="POST" action="{{route('event.update', $event->id)}}" enctype="multipart/form-data">
        @csrf

        <div class="form-group">
            <label>Афиша (jpg/webp, до 5 МБ)</label>
            @if($event->image)
                <div class="mb-2">
                    <img src="{{ $event->image_url }}" alt="Текущая афиша" class="current-event-image">
                </div>
            @endif
            <input type="file" name="image" accept=".jpg,.jpeg,.webp">
            @error('image') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label>Название</label>
            <input type="text" placeholder="Новое мероприятие" name="title" value="{{ old('title', $event->title) }}">
            @error('title') <span class="error">* {{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            @php $selectedTags = old('tags', $event->tags ?? []); @endphp
            <x-tag-picker label="Теги мероприятия" :available-tags="$availableTags" :selected-tags="$selectedTags" />
            @error('tags') <span class="error">* {{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label>Описание</label>
            <textarea rows="3" name="description">{{ old('description', $event->description) }}</textarea>
            @error('description') <span class="error">* {{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label>Минимум записей</label>
            <input type="number" name="min_entries" min="0" value="{{ old('min_entries', $event->min_entries ?? 0) }}">
            @error('min_entries') <span class="error">* {{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label>Максимум записей</label>
            <input type="number" name="max_entries" min="1" value="{{ old('max_entries', $event->max_entries ?? 10) }}">
            @error('max_entries') <span class="error">* {{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label>Дата и время</label>
            <input type="datetime-local" name="date" value="{{ old('date', $event->date ? \Carbon\Carbon::parse($event->date)->format('Y-m-d\TH:i') : '') }}">
            @error('date') <span class="error">* {{ $message }}</span> @enderror
        </div>

        <div class="form-group map-field">
            <label>Место проведения</label>
            <input type="text" name="place" id="place-input" placeholder="Введите адрес или выберите на карте" value="{{ old('place', $event->place) }}">
            @error('place') <span class="error">* {{ $message }}</span> @enderror
            <div id="map" class="form-map form-map-edit"></div>
            <small class="form-help">Уточните место на карте или введите адрес</small>
            <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude', $event->latitude) }}">
            <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude', $event->longitude) }}">
            @error('latitude') <span class="error">* {{ $message }}</span> @enderror
            @error('longitude') <span class="error">* {{ $message }}</span> @enderror
        </div>

        <input type="submit" class="btn btn-primary" value="Сохранить">
    </form>
</section>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const eventForm = document.getElementById('event-edit-form');
    eventForm?.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !event.target.matches('textarea, button, [type="submit"]')) {
            event.preventDefault();
        }
    });

    const defaultLat = Number(@json(old('latitude', $event->latitude ?? 55.751244)));
    const defaultLng = Number(@json(old('longitude', $event->longitude ?? 37.618423)));
    const map = L.map('map', { attributionControl: false }).setView([defaultLat, defaultLng], 13);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors'
    }).addTo(map);
    L.control.attribution({ prefix: false }).addTo(map);

    const markerIcon = L.divIcon({
        className: 'custom-marker',
        html: '<img src="{{ asset('images/event-placeholder.svg') }}" alt="">',
        iconSize: [26, 32],
        iconAnchor: [13, 32],
        popupAnchor: [0, -30]
    });
    const marker = L.marker([defaultLat, defaultLng], { icon: markerIcon, draggable: true }).addTo(map);
    const placeInput = document.getElementById('place-input');
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');

    function updateCoordinates(lat, lng, updatePlace = true) {
        latInput.value = Number(lat).toFixed(7);
        lngInput.value = Number(lng).toFixed(7);

        if (updatePlace) {
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latInput.value}&lon=${lngInput.value}`)
                .then(response => response.json())
                .then(data => { if (data?.display_name) placeInput.value = data.display_name; })
                .catch(() => { placeInput.value = `${latInput.value}, ${lngInput.value}`; });
        }
    }

    map.on('click', function (event) {
        marker.setLatLng(event.latlng);
        updateCoordinates(event.latlng.lat, event.latlng.lng);
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
            fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(results => {
                    if (!results.length) return;
                    const position = [Number(results[0].lat), Number(results[0].lon)];
                    marker.setLatLng(position);
                    map.setView(position, 15);
                    updateCoordinates(position[0], position[1], false);
                    placeInput.value = results[0].display_name;
                })
                .catch(() => {});
        }, 800);
    });

    eventForm.addEventListener('submit', function (event) {
        if (!latInput.value || !lngInput.value) {
            event.preventDefault();
            alert('Пожалуйста, укажите место на карте.');
        }
    });
</script>

@endsection