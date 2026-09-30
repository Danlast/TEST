@extends('template.app')

@section('page')
<section class="content">
    <h2>Удалить объявление</h2>
    <p>Вы уверены, что хотите удалить это объявление?</p>
    <form method="POST" action="{{ route('exchange.destroy', $exchange->id) }}">
        @csrf
        @method('DELETE')
        <button class="btn btn-delete">Удалить</button>
    </form>
</section>
@endsection
