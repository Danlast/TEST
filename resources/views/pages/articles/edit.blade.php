@extends('template.app')

@section('page')
<section class="content-shell form-card">
    <h2>Редактировать статью</h2>
    <form method="POST" action="{{ route('articles.update', $article) }}" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label for="banner">Баннер статьи</label>
            @if($article->banner_url)
                <img src="{{ $article->banner_url }}" alt="Текущий баннер статьи" class="article-banner-preview">
            @endif
            <input type="file" name="banner" id="banner" accept="image/jpeg,image/png,image/webp">
            @error('banner') <span class="error">* {{ $message }}</span> @enderror
        </div>

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
            @php $selectedTags = old('tags', $article->tags ?? []); @endphp
            <x-tag-picker label="Теги статьи" :available-tags="$availableTags" :selected-tags="$selectedTags" />
            @error('tags') <span class="error">* {{ $message }}</span> @enderror
        </div>

        <button class="btn btn-primary">Сохранить</button>
    </form>
</section>
@endsection
