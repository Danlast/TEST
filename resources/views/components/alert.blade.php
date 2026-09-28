@if(session('success'))
    <div class="toast toast-success" role="status" data-toast>
        {{ session('success') }}
        <button type="button" class="toast-close" aria-label="Закрыть уведомление" data-toast-close>&times;</button>
    </div>
@endif
@if(session('error'))
    <div class="toast toast-error" role="alert" data-toast>
        {{ session('error') }}
        <button type="button" class="toast-close" aria-label="Закрыть уведомление" data-toast-close>&times;</button>
    </div>
@endif

