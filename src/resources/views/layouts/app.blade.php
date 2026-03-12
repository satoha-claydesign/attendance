<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>coachtech ATTENDANCE</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    <link rel="stylesheet" href="cdnjs.cloudflare.com" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    @yield('css')
    <!-- アイコンの読み込み -->
        <meta name="csrf-token" content="{{ csrf_token() }}">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>

<body>
    <header class="header">
        <div class="header__inner">
        <div class="header-utilities">
            <a class="header__logo" href="/attendance">
            <img src="{{ asset('storage/images/COACHTECH_logo.png') }}" alt="" />
            </a>
        </div>
        <!-- <div class="search-form__item">
            <form class="keyword-search" action="/search" method="get">
            <input type="text" class="search-form__item-input keyword-input" name="keyword"
                    placeholder="何をお探しですか" value="{{ request('keyword') }}" />
            <div class="search-form__actions">
                <input class="item__search-button" type="submit" value="検索">
            </div>
            </form>
        </div> -->
        <div class="header-utilities">
            <nav>
            <ul class="header-nav">
                @if(Auth::check())
                <li class="header-nav__item">
                <a class="header-nav__link" href="/attendance">勤怠</a>
                </li>
                <li class="header-nav__item">
                <a class="header-nav__link" href="/attendance/list">勤怠一覧</a>
                </li>
                <li class="header-nav__item">
                <a class="header-nav__link" href="/stamp_correction_request/list">申請</a>
                </li>
                <li class="header-nav__item">
                <form class="form" action="/logout" method="post">
                    @csrf
                    <button class="header-nav__link">ログアウト</button>
                </form>
                </li>
                
                @endif
            </ul>
            </nav>
        </div>
        </div>
    </header>
    <main>
        @yield('content')
    </main>
</body>

</html>
