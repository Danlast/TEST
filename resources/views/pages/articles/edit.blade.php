@extends('template.app')

@section('page')
<section class="form-card">
    <h2>Редактировать статью</h2>
    <form method="POST" action="{{ route('articles.update', $article) }}">
        @csrf
        <div class="form-group">
            <label for="title">Заголовок</label>
            <input type="text" name="title" id="title" value="{{ old('title', $article->title) }}" required>
            @error('title') <span class="error">* {{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label for="description">Краткое описание</label>
            <textarea name="description" id="description" rows="3">{{ old('description', $article->description) }}</textarea>
            @error('description') <span class="error">* {{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label for="content">Текст статьи</label>
            <textarea name="content" id="content" rows="8" required>{{ old('content', $article->content) }}</textarea>
            @error('content') <span class="error">* {{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label>Теги статьи</label>
            @php $selectedTags = old('tags', $article->tags ?? []); @endphp
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

        <button class="btn btn-primary">Сохранить</button>
    </form>
</section>
@endsection
