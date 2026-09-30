@extends('template.app')

@section('page')
<section class="content">
    <h2>Панель модератора</h2>

    <div class="form-group">
        <label>Поиск пользователя клуба</label>
        <input type="text" placeholder="Введите имя или email">
    </div>

    <div class="form-group">
        <h3>Список пользователей клуба</h3>
        <ul>
            <li>Ольга Белова — активен</li>
            <li>Дмитрий Михайлов — активен</li>
            <li>Наталья Соколова — заблокирован</li>
        </ul>
    </div>

    <div class="form-group">
        <h3>Блокировка пользователя</h3>
        <button class="btn btn-delete">Заблокировать</button>
    </div>
</section>
@endsection
