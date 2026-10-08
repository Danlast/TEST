@props(['paginator', 'target'])

@if($paginator->hasMorePages())
    <button
        type="button"
        class="btn btn-outline load-more-button"
        data-load-more
        data-url="{{ $paginator->nextPageUrl() }}"
        data-target="{{ $target }}"
    >Загрузить ещё</button>
@endif