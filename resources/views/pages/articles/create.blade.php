@extends('template.app')

@section('page')
<section class="form-card">
    <h2>Новая статья</h2>
    <form method="POST" action="{{ route('articles.store') }}">
        @csrf
        <div class="form-group">
            <label for="title">Заголовок</label>
            <input type="text" name="title" id="title" value="{{ old('title') }}" required>
            @error('title') <span class="error">* {{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label for="description">Краткое описание</label>
            <textarea name="description" id="description" rows="3">{{ old('description') }}</textarea>
            @error('description') <span class="error">* {{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label for="content">Текст статьи</label>
            <textarea name="content" id="content" rows="8" required>{{ old('content') }}</textarea>
            @error('content') <span class="error">* {{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label>Теги статьи</label>
            @php $selectedTags = old('tags', []); @endphp
            <div class="tag-select-wrap">
                <select name="tags[]" class="tag-select" multiple size="6">
                    @foreach($availableTags as $value => $label)
                        <option value="{{ $value }}" {{ in_array($value, $selectedTags, true) ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <small class="form-help">Можно выбрать несколько тегов.</small>
            </div>
            @error('tags') <span class="error">* {{ $message }}</span> @enderror
        </div>

        <button class="btn btn-primary">Опубликовать</button>
    </form>
</section>
@endsection
