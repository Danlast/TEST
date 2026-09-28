<!-- ========== ХЕДЕР ========== -->
<header class="header" id="site-header">
    <div class="header-inner">
        <a href="{{ route('home') }}" class="logo">Книжный</a>
        <div class="nav">
            <a href="{{ route('home') }}">Главная</a>
            <a href="{{ route('event.index') }}">Мероприятия</a>
            <a href="{{ route('club.index') }}">Клубы</a>
            <a href="{{ route('articles.index') }}">Статьи</a>
            @auth
                <a href="{{ route('exchange.index') }}">Обмен</a>
            @endauth

            @guest
                <a href="{{ route('show.reg') }}">Регистрация</a>
                <a href="{{ route('show.login') }}">Вход</a>
            @endguest

            @auth
                @if(auth()->user()->role === \App\Enums\UserRole::CLUB)
                    <a href="{{ route('club.profile', auth()->user()->id) }}">Профиль</a>
                @else
                    <a href="{{ route('profile') }}">Профиль</a>
                @endif


                @if(auth()->user()->role?->isStaff() || auth()->user()->role?->managesClubContent())
                    <a href="{{ route('event.create') }}">Добавить мероприятие</a>
                @endif

                @if(auth()->user()->role === \App\Enums\UserRole::ADMIN)
                    <a href="{{ route('admin.panel') }}">Админ-панель</a>
                @endif

                <form action="{{ route('logout') }}" method="POST" class="logout-form">
                    @csrf
                    <input type="submit" value="Выйти">
                </form>
            @endauth
        </div>
    </div>
</header>

<script>
    const header = document.getElementById('site-header');
    let lastScrollTop = 0;

    window.addEventListener('scroll', () => {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

        if (scrollTop > lastScrollTop && scrollTop > 80) {
            header.classList.add('is-hidden');
        } else {
            header.classList.remove('is-hidden');
        }

        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
    });
</script>