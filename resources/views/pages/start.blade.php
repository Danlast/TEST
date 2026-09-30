@extends('template.app')

@section('page')
    <div class="content start-page">
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
                        <x-event-card :event="$event" />
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
            const placeholderImage = @json(asset('images/event-placeholder.svg'));
            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
            })[character]);
            const createCustomIcon = (image) => L.divIcon({
                className: 'event-map-marker',
                html: `<img src="${escapeHtml(image || placeholderImage)}" alt="">`,
                iconSize: [26, 32],
                iconAnchor: [13, 32],
                popupAnchor: [0, -30]
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
                        icon: createCustomIcon(data.image)
                    }).addTo(map);

                    const tags = (data.tags || []).map(escapeHtml).join(', ');
                    const clubLink = data.clubUrl
                        ? `<span class="popup-club">Клуб: <a href="${escapeHtml(data.clubUrl)}">${escapeHtml(data.clubName)}</a></span>`
                        : '<span></span>';
                    const popupContent = `
                        <div class="popup-card">
                            <img class="popup-poster" src="${escapeHtml(data.image)}" alt="Афиша мероприятия ${escapeHtml(data.title)}">
                            <h3>${escapeHtml(data.title)}</h3>
                            ${tags ? `<p class="popup-tags">Теги: ${tags}</p>` : ''}
                            <p class="popup-place">${escapeHtml(data.place)}</p>
                            <div class="popup-meta-row">
                                ${clubLink}
                                <span class="popup-date">${escapeHtml(data.date)}</span>
                            </div>
                            <p class="popup-registrations">Записалось: ${escapeHtml(data.registeredCount)}/${escapeHtml(data.maxEntries)}</p>
                            <a href="${escapeHtml(data.url)}" class="btn btn-outline popup-link">Подробнее</a>
                        </div>
                    `;

                    marker.bindPopup(popupContent);

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
                                    placeSearchMarker = L.marker(coordinates, { icon: createCustomIcon('') }).addTo(map);
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