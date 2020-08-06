<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="author" content="PoemWiki,@yield('author')">
    <meta name="description" content="PoemWiki">
    <meta name="keyword" content="poem,poetry,poet,诗,诗歌诗人,@yield('author'),@yield('title')">
    @include('layouts.icon')
    @include('layouts.analyze')

    <title>@yield('title') - {{config('app.name')}}</title>

    <!-- Fonts -->
    <link href="{{ asset('css/post.css') }}" rel="stylesheet">
</head>
<body class="position-ref">
    <div class="top-right links no-select">
        <a class="site-name" href="{{$randomPoemUrl}}">POEM&#0010;Wiki</a>
    </div>
    <main class="post">
        @yield('content')
    </main>
</body>
</html>
