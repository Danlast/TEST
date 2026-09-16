@extends('template.app')
@section('page')

<!-- ========== ИЗМЕНЕНИ МЕРОПРИЯТИЯ ========== -->
<section class="form-card">
    <h2>Изменить мероприятие</h2>
    <form method="POST" action="{{route('event.update', $event->id)}}" enctype="multipart/form-data">
        @csrf

        <div class="form-group">
            <label>Название</label>
            <input type="text" placeholder="Новое мероприятие" name="title" value="{{ $event->title }}">
            @error('title')
                <span class="error">* {{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label>Дата и время</label>
            <input type="datetime-local" name="date" value="{{ $event->date ? \Carbon\Carbon::parse($event->date)->format('Y-m-d\TH:i') : '' }}">
            @error('date')
                <span class="error">* {{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label>Место</label>
            <input type="text" placeholder="Казань, IT-парк" name="place" value="{{ $event->place }}">
            @error('place')
                <span class="error">* {{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label>Описание</label>
            <textarea rows="3" name="description">{{ $event->description }}</textarea>
            @error('description')
                <span class="error">* {{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label>Теги мероприятия</label>
            @php $selectedTags = $event->tags ?? []; @endphp
            <div class="tag-select-wrap">
                <select name="tags[]" class="tag-select" multiple size="7">
                    @foreach($availableTags as $value => $label)
                        <option value="{{ $value }}" {{ in_array($value, $selectedTags, true) ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <small class="form-help">Можно выбрать несколько тегов. Удерживайте Ctrl/Cmd для выбора нескольких вариантов.</small>
            </div>
            @error('tags')
                <span class="error">* {{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label>Минимум записей</label>
            <input type="number" name="min_entries" min="0" value="{{ $event->min_entries ?? 0 }}">
            @error('min_entries')
                <span class="error">* {{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label>Максимум записей</label>
            <input type="number" name="max_entries" min="1" value="{{ $event->max_entries ?? 10 }}">
            @error('max_entries')
                <span class="error">* {{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label></label>Афиша (jpg/webp, до 50kb)</label>
            @if($event->image)
                <div class="mb-2">
                    <img src="{{ $event->image_url }}" alt="Текущая афиша" class="current-event-image">
                </div>
            @endif
            <input type="file" name="image" accept=".jpg,.jpeg,.webp">
            @error('image')
                <span class="error">{{ $message }}</span>
            @enderror
        </div>

        <input type="submit" class="btn btn-primary" value="Сохранить">
    </form>
</section>

@endsection