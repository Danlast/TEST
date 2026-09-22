@props([
    'name' => 'tags[]',
    'availableTags' => [],
    'selectedTags' => [],
    'label' => 'Теги',
    'help' => null,
])

@php
    $selectedTags = array_values((array) $selectedTags);
    $selectedLabels = collect($availableTags)
        ->only($selectedTags)
        ->values()
        ->all();
@endphp

<details class="tag-picker" data-tag-picker {{ count($selectedTags) > 0 ? 'open' : '' }}>
    <summary class="tag-picker-summary">
        <span>{{ $label }}</span>
        <span class="tag-picker-status">
            <span data-tag-count>{{ count($selectedTags) }} выбрано</span>
            <span class="tag-picker-chevron" aria-hidden="true">▾</span>
        </span>
    </summary>

    <div class="tag-picker-panel">
        <div class="tag-picker-toolbar">
            <span class="tag-picker-hint">Можно выбрать несколько вариантов</span>
            <button type="button" class="tag-picker-clear" data-tag-clear>Очистить</button>
        </div>

        <div class="tag-options">
            @foreach($availableTags as $value => $tagLabel)
                <label class="tag-option">
                    <input type="checkbox" name="{{ $name }}" value="{{ $value }}" {{ in_array($value, $selectedTags, true) ? 'checked' : '' }}>
                    <span>{{ $tagLabel }}</span>
                </label>
            @endforeach
        </div>

        @if($help)
            <small class="form-help">{{ $help }}</small>
        @endif
    </div>
</details>
