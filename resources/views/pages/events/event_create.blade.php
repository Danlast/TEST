@extends('template.app')
@section('page')

<section class="content-shell form-card">
    <h2>Добавить мероприятие</h2>
    <form method="POST" action="{{ route('event.store') }}" enctype="multipart/form-data">
        @csrf

        {{-- Название --}}
        <div class="form-group">
            <label>Название</label>
            <input type="text" name="title" value="{{ old('title') }}">
            @error('title') <span class="error">* {{ $message }}</span> @enderror
        </div>

        {{-- Дата и время --}}
        <div class="form-group">
            <label>Дата и время</label>
            <input type="datetime-local" name="date" value="{{ old('date') }}">
            @error('date') <span class="error">* {{ $message }}</span> @enderror
        </div>

        {{-- Блок выбора места на карте --}}
        <div class="form-group">
            <label>Место проведения</label>
            <div class="map-field">
                <input type="text" name="place" id="place-input" placeholder="Введите адрес или выберите на карте" value="{{ old('place') }}">
                <div id="map" class="form-map form-map-create"></div>
                <small class="form-help">Кликните по карте, чтобы указать точное место, или воспользуйтесь поиском адреса</small>
            </div>
            @error('place') <span class="error">* {{ $message }}</span> @enderror

            {{-- Скрытые поля координат --}}
            <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}">
            <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}">
            @error('latitude') <span class="error">* {{ $message }}</span> @enderror
            @error('longitude') <span class="error">* {{ $message }}</span> @enderror
        </div>

        {{-- Описание --}}
        <div class="form-group">
            <label>Описание</label>
            <textarea name="description" rows="3">{{ old('description') }}</textarea>
            @error('description') <span class="error">* {{ $message }}</span> @enderror
        </div>

        {{-- Теги --}}
        <div class="form-group">
            @php $selectedTags = old('tags', []); @endphp
            <x-tag-picker label="Теги мероприятия" :available-tags="$availableTags" :selected-tags="$selectedTags" />
            @error('tags') <span class="error">* {{ $message }}</span> @enderror
        </div>

        {{-- Минимум / Максимум записей --}}
        <div class="form-group">
            <label>Минимум записей</label>
            <input type="number" name="min_entries" min="0" placeholder="" value="{{ old('min_entries', 1) }}">
            @error('min_entries') <span class="error">* {{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label>Максимум записей</label>
            <input type="number" name="max_entries" min="1" placeholder="" value="{{ old('max_entries', 10) }}">
            @error('max_entries') <span class="error">* {{ $message }}</span> @enderror
        </div>

        {{-- Афиша --}}
        <div class="form-group">
            <label>Афиша (jpg/webp, до 50kb)</label>
            <input type="file" name="image" accept=".jpg,.jpeg,.webp">
            @error('image') <span class="error">{{ $message }}</span> @enderror
        </div>

        <input type="submit" class="btn btn-primary" value="Создать">
    </form>
</section>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Координаты по умолчанию (Москва), если не заданы
    const defaultLat = {{ old('latitude') ?? 55.751244 }};
    const defaultLng = {{ old('longitude') ?? 37.618423 }};

    // Инициализация карты
    const map = L.map('map', { attributionControl: false }).setView([defaultLat, defaultLng], 13);

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors'
    }).addTo(map);
    L.control.attribution({ prefix: false }).addTo(map);

    // Кастомный маркер
    const markerIcon = L.divIcon({
        className: 'custom-marker',
        html: '📍',
        iconSize: [40, 40],
        iconAnchor: [20, 40],
        popupAnchor: [0, -40]
    });

    // Добавляем маркер
    let marker = L.marker([defaultLat, defaultLng], { icon: markerIcon, draggable: true }).addTo(map);

    // Поля ввода
    const placeInput = document.getElementById('place-input');
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');

    // Функция обновления скрытых полей и поля адреса (обратное геокодирование)
    function updateCoordinates(lat, lng, updatePlace = true) {
        lat = parseFloat(lat).toFixed(7);
        lng = parseFloat(lng).toFixed(7);
        latInput.value = lat;
        lngInput.value = lng;

        if (updatePlace) {
            // Обратное геокодирование через Nominatim
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

    // Установить начальные координаты, если они есть (из old)
    if (defaultLat && defaultLng) {
        updateCoordinates(defaultLat, defaultLng);
    }

    // Клик по карте – перемещаем маркер и обновляем координаты
    map.on('click', function(e) {
        marker.setLatLng(e.latlng);
        updateCoordinates(e.latlng.lat, e.latlng.lng);
    });

    // Перетаскивание маркера
    marker.on('dragend', function(e) {
        const pos = marker.getLatLng();
        updateCoordinates(pos.lat, pos.lng);
    });

    // Поиск адреса (геокодирование) при изменении поля place
    let searchTimeout;
    placeInput.addEventListener('input', function() {
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
                        updateCoordinates(lat, lng, false); // не перезаписываем адрес, который ввёл пользователь
                        placeInput.value = first.display_name; // уточняем адрес
                    }
                })
                .catch(() => {});
        }, 800);
    });

    // При отправке формы убедимся, что координаты проставлены
    document.querySelector('form').addEventListener('submit', function(e) {
        if (!latInput.value || !lngInput.value) {
            e.preventDefault();
            alert('Пожалуйста, укажите место на карте.');
            return false;
        }
    });
});
</script>
@endsection