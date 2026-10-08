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

        document.addEventListener('submit', async (event) => {
            const form = event.target.closest('form.comment-form, form.comment-reply-form, form.comment-edit-form, form.comment-delete-form');
            if (!form) return;

            event.preventDefault();
            const submitButton = form.querySelector('button:not([type]), [type="submit"]');
            const textarea = form.querySelector('textarea[name="content"]');
            const status = form.querySelector('[data-comment-status]') || document.createElement('p');
            status.dataset.commentStatus = '';
            status.classList.add('comment-status');
            status.setAttribute('aria-live', 'polite');
            if (!status.isConnected) form.append(status);
            status.textContent = '';
            if (submitButton) submitButton.disabled = true;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const result = await response.json();

                if (!response.ok) {
                    const messages = Object.values(result.errors || {}).flat();
                    throw new Error(messages.join(' ') || result.message || 'Не удалось отправить комментарий.');
                }

                const thread = form.closest('.comment-thread');

                if (form.matches('.comment-edit-form')) {
                    thread.querySelector('.comment-content').textContent = result.content;
                    form.closest('.comment-menu')?.classList.remove('is-open');
                    status.textContent = result.message;
                    return;
                }

                if (form.matches('.comment-delete-form')) {
                    const section = thread.closest('[data-comment-section]');
                    if (result.tombstone) {
                        const content = thread.querySelector('.comment-content');
                        content.textContent = 'Комментарий был удален';
                        content.classList.add('comment-deleted');
                        thread.querySelector('.comment-author')?.remove();
                        thread.querySelector('.comment-actions')?.remove();
                    } else {
                        const parentReplies = thread.parentElement.closest('.comment-replies');
                        thread.remove();
                        if (parentReplies) {
                            const directCount = parentReplies.querySelectorAll(':scope > .comment-replies-list > .comment-thread').length;
                            if (directCount === 0) {
                                parentReplies.remove();
                            } else {
                                parentReplies.querySelector(':scope > summary').textContent = `Ответы (${directCount})`;
                            }
                        } else if (section && section.querySelector('[data-comments-list]').childElementCount === 0) {
                            const emptyHint = document.createElement('p');
                            emptyHint.className = 'empty-hint';
                            emptyHint.textContent = 'Комментариев пока нет.';
                            section.append(emptyHint);
                        }
                    }

                    status.textContent = result.message;
                    return;
                }

                const template = document.createElement('template');
                template.innerHTML = result.html.trim();
                const newThread = template.content.firstElementChild;

                if (form.matches('.comment-reply-form')) {
                    const parentThread = form.closest('.comment-thread');
                    let replies;

                    if (result.sameLevel) {
                        replies = parentThread.parentElement.closest('.comment-replies');
                        replies.querySelector(':scope > .comment-replies-list').append(newThread);
                    } else {
                        replies = parentThread.querySelector(':scope > .comment-replies');

                        if (!replies) {
                            replies = document.createElement('details');
                            replies.className = 'comment-replies';
                            replies.open = true;
                            replies.innerHTML = '<summary>Ответы (0)</summary><div class="comment-replies-list"></div>';
                            parentThread.append(replies);
                        }

                        replies.open = true;
                        replies.querySelector('.comment-replies-list').append(newThread);
                    }

                    replies.open = true;
                    const summary = replies.querySelector('summary');
                    const replyCount = replies.querySelectorAll(':scope > .comment-replies-list > .comment-thread').length;
                    summary.textContent = `Ответы (${replyCount})`;
                    form.closest('details').open = false;
                } else {
                    const section = form.closest('[data-comment-section]');
                    const list = section.querySelector('[data-comments-list]');
                    list.append(newThread);
                    section.querySelector('.empty-hint')?.remove();
                    form.reset();
                }

                status.textContent = result.message;
            } catch (error) {
                status.textContent = error.message;
                status.classList.add('comment-status-error');
            } finally {
                if (submitButton) submitButton.disabled = false;
            }
        });
    </script>
    <script>
        document.addEventListener('click', async (event) => {
            const button = event.target.closest('[data-load-more]');
            if (!button || button.disabled) return;

            const targetSelector = button.dataset.target;
            const target = document.querySelector(targetSelector);
            if (!target) return;

            button.disabled = true;
            button.setAttribute('aria-busy', 'true');

            try {
                const response = await fetch(button.dataset.url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
                    credentials: 'same-origin',
                });
                if (!response.ok) throw new Error('Не удалось загрузить записи.');

                const documentFragment = new DOMParser().parseFromString(await response.text(), 'text/html');
                const nextTarget = documentFragment.querySelector(targetSelector);
                if (!nextTarget) throw new Error('Не удалось найти список записей.');

                Array.from(nextTarget.children).forEach((item) => target.append(item));
                const nextButton = Array.from(documentFragment.querySelectorAll('[data-load-more]'))
                    .find((item) => item.dataset.target === targetSelector);

                if (nextButton) {
                    button.dataset.url = nextButton.dataset.url;
                    button.disabled = false;
                    button.removeAttribute('aria-busy');
                } else {
                    button.remove();
                }
            } catch (error) {
                button.disabled = false;
                button.removeAttribute('aria-busy');
                button.textContent = 'Не удалось загрузить. Повторить';
            }
        });
    </script>
</body>
</html>