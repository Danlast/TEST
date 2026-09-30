@extends('template.app')

@section('page')
<section class="content profile-hidden">
    <h1>Профиль скрыт</h1>
    <p>Владелец ограничил доступ к этой странице.</p>
    <a href="{{ route('home') }}" class="btn btn-outline">На главную</a>
</section>
@endsection