@extends('template.app')
@section('page')

<section class="content">
<div class="delete-confirm">
    <div class="delete-confirm-header">Подтверждение удаления</div>
    <div class="delete-confirm-body">
        <p>Удалить мероприятие <strong>{{$event->title}}</strong>?</p>
        <p class="delete-warning">Данные будут удалены без возможности восстановления.</p>
        <div class="flex">
            <form method="POST" action="{{ route('event.destroy', $event->id)}}"  enctype="multipart/form-data">
                @csrf
                @method('DELETE')
                <input type="submit" class="btn btn-delete" value="Да, удалить">
            </form>
            <a href="{{ route('event.show', $event->id)}}" class="btn btn-outline">Отмена</a>
        </div>
    </div>
</div>
</section>


@endsection