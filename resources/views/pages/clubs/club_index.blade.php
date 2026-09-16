@extends('template.app')

@section('page')
<div class="container page-container">
    <div class="page-shell">
        <div class="page-header">
            <div>
                <h2 class="section-title page-title">Клубы</h2>
                <p class="page-subtitle">Открывайте клубы и переходите на их страницы.</p>
            </div>
        </div>

        <form method="GET" class="filter-panel">
            <div class="filter-grid">
                <div class="filter-field">
                    <label>Поиск клуба</label>
                    <input type="text" name="q" value="{{ $query ?? '' }}" placeholder="Введите название клуба">
                </div>
            </div>
            <input type="submit" class="btn btn-outline form-action" value="Найти">
        </form>

        <div class="object-grid">
            @if($clubs->isEmpty())
                <p>Клубов пока нет.</p>
            @else
                @foreach($clubs as $club)
                    <div class="card">
                        <div class="card-content">
                            <h3 class="card-title">{{ $club->username }}</h3>
                            <p>{{ $club->email }}</p>
                            <a href="{{ route('club.profile', $club->id) }}" class="btn btn-outline">Подробнее</a>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
        {{ $clubs->links() }}
    </div>
</div>
@endsection
