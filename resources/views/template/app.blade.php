<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Books</title>
    <link rel="stylesheet" href="{{ asset('style.css') }}">
</head>
<body>
    <div class="page-wrap">
        @include('components.header')
        <div class="container">
            @include('components.alert')
            @yield('page')
        </div>
        @include('components.footer')
    </div>
    <script>
        document.querySelectorAll('[data-tag-picker]').forEach((picker) => {
            const count = picker.querySelector('[data-tag-count]');
            const checkboxes = picker.querySelectorAll('input[type="checkbox"]');
            const clearButton = picker.querySelector('[data-tag-clear]');

            if (!count) return;

            const updateCount = () => {
                count.textContent = `${picker.querySelectorAll('input[type="checkbox"]:checked').length} выбрано`;
            };

            checkboxes.forEach((checkbox) => checkbox.addEventListener('change', updateCount));
            clearButton?.addEventListener('click', () => {
                checkboxes.forEach((checkbox) => { checkbox.checked = false; });
                updateCount();
            });
        });
    </script>
</body>
</html>