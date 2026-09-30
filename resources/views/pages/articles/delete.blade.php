@extends('template.app')

@section('page')
<section class="content">
    <h2>Удаление статьи</h2>
    <p>Вы уверены, что хотите удалить статью <strong>{{ $article->title }}</strong>?</p>
    <form method="POST" action="{{ route('articles.destroy', $article) }}" class="section-top-sm">
        @csrf
        @method('DELETE')
        <button class="btn btn-delete">Удалить</button>
        <a href="{{ route('articles.show', $article) }}" class="btn btn-outline">Отмена</a>
    </form>
</section>
@endsection
