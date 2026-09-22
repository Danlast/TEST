@extends('template.app')

@section('page')
    <div class="content-shell start-page">
        <section class="start-map-section" aria-labelledby="map-title">
            <h2 id="map-title" class="section-title map-title">Карта мероприятий</h2>
            <div id="map" class="map-container"></div>
            <div class="map-info">
                <span id="markers-count">Загрузка маркеров...</span>
            </div>
        </section>

        <section id="events-list" aria-labelledby="events-title">
            <h2 id="events-title" class="section-title">Лента мероприятий</h2>

            <form method="GET" class="filter-panel filter-panel-spaced">
            <div class="filter-grid">
                <div class="filter-field search-field">
                    <label for="event-search">Название мероприятия</label>
                    <input type="search" id="event-search" name="q" value="{{ $query ?? '' }}" placeholder="Введите название" autocomplete="off">
                </div>

                <div class="filter-field search-field">
                    <label for="event-place-search">Место проведения</label>
                    <input type="search" id="event-place-search" name="place" value="{{ $place ?? '' }}" placeholder="Введите место" autocomplete="off">
                </div>

                <div class="filter-field">
                    <label>От</label>
                    <input type="date" name="date_from" value="{{ $dateFrom ?? '' }}">
                </div>

                <div class="filter-field">
                    <label>До</label>
                    <input type="date" name="date_to" value="{{ $dateTo ?? '' }}">
                </div>
            </div>

                <x-tag-picker :available-tags="$availableTags ?? []" :selected-tags="$tags ?? []" />

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Найти мероприятия</button>
                    @if(($query ?? '') !== '' || ($place ?? '') !== '' || !empty($dateFrom) || !empty($dateTo) || !empty($tags ?? []))
                        <a href="{{ route('home') }}" class="btn btn-outline">Сбросить фильтры</a>
                    @endif
                </div>
            </form>

            @if($events->count() > 0)
                <div class="object-grid">
                    @foreach ($events as $event)
                        <div class="card" data-event-id="{{ $event->id }}">
                            <div class="card-img">
                                <img src="{{ $event->image_url }}" alt="{{ $event->title }}">
                            </div>
                            <div class="card-content">
                                <h3 class="card-title">{{ $event->title }}</h3>
                                <div class="card-meta">
                                    {{ $event->date }} {{ $event->place }}
                                </div>
                                @if(!empty($event->tags_labels))
                                    <div class="card-meta event-tags">
                                        {{ implode(', ', $event->tags_labels) }}
                                    </div>
                                @endif
                                <a href="{{ route('event.show', $event->id) }}" class="btn btn-outline">Подробнее</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state">
                    <h3>Мероприятий не найдено</h3>
                    <a href="{{ route('home') }}" class="btn btn-outline">Показать все мероприятия</a>
                </div>
            @endif
        </section>
    </div>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Данные для маркеров – переданы из контроллера
            const markersData = @json($mapMarkers ?? []);

            if (!document.getElementById('map')) return;

            // Карта по умолчанию на Москву, если нет маркеров
            const map = L.map('map', { attributionControl: false }).setView([55.751244, 37.618423], 10);

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors'
            }).addTo(map);
            L.control.attribution({ prefix: false }).addTo(map);

            // Кастомная иконка
            const createCustomIcon = () => L.divIcon({
                className: 'custom-marker',
                html: '📍',
                iconSize: [42, 42],
                iconAnchor: [21, 42],
                popupAnchor: [0, -42]
            });

            let markersCount = 0;
            const markersGroup = [];
            let placeSearchMarker = null;
            const placeInput = document.getElementById('event-place-search');

            markersData.forEach((data) => {
                const lat = parseFloat(data.latitude);
                const lon = parseFloat(data.longitude);

                if (!isNaN(lat) && !isNaN(lon)) {
                    const marker = L.marker([lat, lon], {
                        icon: createCustomIcon()
                    }).addTo(map);

                    const popupContent = `
                        <div class="popup-card">
                            <h3>${data.title}</h3>
                            <p> ${data.place}</p>
                            <p> ${new Date(data.date).toLocaleDateString('ru-RU')}</p>
                            <a href="${data.url}" class="btn btn-primary popup-link">
                                Подробнее
                            </a>
                        </div>
                    `;

                    marker.bindPopup(popupContent);

                    // При клике на маркер – скролл к карточке
                    marker.on('click', function() {
                        const card = document.querySelector(`[data-event-id="${data.id}"]`);
                        if (card) {
                            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            card.style.boxShadow = '0 0 0 4px rgba(245, 158, 11, 0.5)';
                            setTimeout(() => { card.style.boxShadow = ''; }, 2000);
                        }
                    });

                    markersGroup.push(marker);
                    markersCount++;
                }
            });

            document.getElementById('markers-count').textContent =
                `Показано мероприятий: ${markersCount}`;

            if (markersGroup.length > 0) {
                const group = new L.featureGroup(markersGroup);
                map.fitBounds(group.getBounds().pad(0.1));
            }

            if (placeInput) {
                let placeSearchTimeout;
                placeInput.addEventListener('input', function () {
                    clearTimeout(placeSearchTimeout);
                    const place = placeInput.value.trim();

                    if (place.length < 3) return;

                    placeSearchTimeout = setTimeout(() => {
                        fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(place)}`)
                            .then(response => response.json())
                            .then(results => {
                                if (!results.length) return;

                                const location = results[0];
                                const coordinates = [parseFloat(location.lat), parseFloat(location.lon)];
                                map.setView(coordinates, 13);

                                if (placeSearchMarker) {
                                    placeSearchMarker.setLatLng(coordinates);
                                } else {
                                    placeSearchMarker = L.marker(coordinates, { icon: createCustomIcon() }).addTo(map);
                                }

                                placeSearchMarker.bindPopup(location.display_name).openPopup();
                            })
                            .catch(() => {});
                    }, 700);
                });

                if (placeInput.value.trim().length >= 3) {
                    placeInput.dispatchEvent(new Event('input'));
                }
            }

            // Кнопка скрытия/показа списка
            const toggleBtn = document.getElementById('toggle-map-view');
            const eventsList = document.getElementById('events-list');
            if (toggleBtn && eventsList) {
                toggleBtn.addEventListener('click', function() {
                    if (eventsList.style.display === 'none') {
                        eventsList.style.display = 'block';
                        this.textContent = 'Скрыть список';
                    } else {
                        eventsList.style.display = 'none';
                        this.textContent = 'Показать список';
                        document.querySelector('.map-section').scrollIntoView({ behavior: 'smooth' });
                    }
                });
            }
        });
    </script>
@endsection