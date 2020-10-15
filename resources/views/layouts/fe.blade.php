<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="author" content="PoemWiki,@yield('author')">
    <meta name="description" content="PoemWiki">
    <meta name="keyword" content="@yield('title'),@yield('author'),poem,poetry,poet,诗,诗歌,诗人">
    @include('layouts.icon')
    @include('layouts.analyze')

    <title>@yield('title') - {{config('app.name')}}</title>

    <!-- Fonts -->
    <link href="{{ asset('css/post.css') }}" rel="stylesheet">

    @livewireStyles
</head>
<body class="position-ref">
    <div class="top-right links no-select">
        <a class="site-name no-bg" href="{{$randomPoemUrl}}">POEM&#0010;Wiki</a>
    </div>
    <main class="post">
        @yield('content')
    </main>
    @livewireScripts
    @stack('scripts')
</body>
</html>
