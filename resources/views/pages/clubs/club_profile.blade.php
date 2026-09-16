@extends('template.app')

@section('page')
<section class="form-card">
    <h2>{{ $club->username }}</h2>
    <p><strong>Email:</strong> {{ $club->email }}</p>

    @auth
        @if(auth()->user()->id !== $club->id)
            @if(auth()->user()->club_id === $club->id)
                <form action="{{ route('club.leave', $club->id) }}" method="POST" class="flex">
                    @csrf
                    <input type="submit" class="btn btn-delete" value="Отписаться">
                </form>
            @else
                <form action="{{ route('club.join', $club->id) }}" method="POST" class="flex">
                    @csrf
                    <input type="submit" class="btn btn-primary" value="Присоединиться">
                </form>
            @endif
        @endif
    @endauth

    @if(auth()->user() && auth()->user()->canManageClub($club))
        <hr>
        <h3>Управление клубом</h3>
        <div class="flex">
            <a href="{{ route('event.create') }}" class="btn btn-primary">Создать мероприятие</a>
            <a href="{{ route('club.edit', $club->id) }}" class="btn btn-edit">Редактировать профиль клуба</a>
        </div>

        <h3>Назначить роль пользователю</h3>
        <form action="{{ route('club.assignRole', $club->id) }}" method="POST" class="form-group">
            @csrf
            <label>Email пользователя</label>
            <input type="email" name="email" placeholder="user@example.com">
            <label>Роль</label>
            <select name="role">
                <option value="club_moderator">club_moderator</option>
                <option value="user">user</option>
            </select>
            <input type="submit" class="btn btn-primary" value="Назначить">
        </form>

        <h3>Забанить пользователя</h3>
        <form action="{{ route('club.ban', $club->id) }}" method="POST" class="form-group">
            @csrf
            <label>Email пользователя</label>
            <input type="email" name="email" placeholder="user@example.com">
            <label>Причина</label>
            <input type="text" name="reason" placeholder="Причина бана">
            <input type="submit" class="btn btn-delete" value="Забанить">
        </form>
    @endif

    <hr>
    <h3>Участники клуба</h3>
    <form method="GET" class="form-group">
        <input type="text" name="member" value="{{ $query ?? '' }}" placeholder="Поиск участника">
        <input type="submit" class="btn btn-outline" value="Найти">
    </form>

    @if($members->isNotEmpty())
        <ul>
            @foreach($members as $member)
                <li>
                    <a href="{{ route('user.profile', $member->id) }}">{{ $member->username }}</a>
                    @if(auth()->user() && auth()->user()->canManageClub($club) && auth()->user()->id !== $member->id)
                        <form action="{{ route('club.ban', $club->id) }}" method="POST" class="inline-form">
                            @csrf
                            <input type="hidden" name="user_id" value="{{ $member->id }}">
                            <input type="submit" class="btn btn-delete" value="Забанить">
                        </form>
                    @endif
                </li>
            @endforeach
        </ul>
    @else
        <p>Участников пока нет.</p>
    @endif

    <hr>
    <h3>Мероприятия клуба</h3>
    @if($events->isNotEmpty())
        <ul>
            @foreach($events as $event)
                <li><a href="{{ route('event.show', $event->id) }}">{{ $event->title }}</a></li>
            @endforeach
        </ul>
    @else
        <p>Мероприятий пока нет.</p>
    @endif
</section>
@endsection
