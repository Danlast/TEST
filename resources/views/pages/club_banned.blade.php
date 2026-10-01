@extends('template.app')

@section('page')
<section class="content club-banned-page">
    <h1>Вы были забанены в данном клубе</h1>
    <p>Клуб «{{ $club->username }}» ограничил вам доступ.</p>
    <p><strong>Причина:</strong> {{ $banReason ?: 'Причина не указана.' }}</p>
    <a href="{{ route('home') }}" class="btn btn-outline">На главную</a>
</section>
@endsection