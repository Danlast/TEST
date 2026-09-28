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

        document.querySelectorAll('[data-toast]').forEach((toast) => {
            const closeButton = toast.querySelector('[data-toast-close]');
            let hideTimer;

            const dismiss = () => {
                window.clearTimeout(hideTimer);
                toast.classList.add('toast-dismissed');
                window.setTimeout(() => toast.remove(), 250);
            };

            closeButton?.addEventListener('click', dismiss);
            hideTimer = window.setTimeout(dismiss, 6000);
        });
    </script>
</body>
</html>