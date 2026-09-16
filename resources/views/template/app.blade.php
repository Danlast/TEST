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
        document.querySelectorAll('.tag-filter').forEach((filter) => {
            const count = filter.querySelector('[data-tag-count]');
            const checkboxes = filter.querySelectorAll('input[type="checkbox"]');

            if (!count) return;

            const updateCount = () => {
                count.textContent = `Выбрано: ${filter.querySelectorAll('input[type="checkbox"]:checked').length}`;
            };

            checkboxes.forEach((checkbox) => checkbox.addEventListener('change', updateCount));
        });
    </script>
</body>
</html>