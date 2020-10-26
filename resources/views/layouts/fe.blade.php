<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="author" content="PoemWiki,@yield('author')">
    <meta name="description" content="PoemWiki">
    <meta name="keyword" content="@yield('title'),@yield('author'),poemwiki,poem,poetry,poet,诗,诗歌,诗人">
    @include('layouts.icon')
    @include('layouts.analyze')

    <title>@yield('title') - {{config('app.name')}}</title>

    <!-- Fonts -->
    <link href="{{ asset('css/post.css') }}" rel="stylesheet">

    @livewireStyles
</head>
<body class="position-ref">
    @include('layouts.fe-menu')
    <main>@yield('content')</main>

    @livewireScripts
    @stack('scripts')
</body>

@if(Auth::check())
    @php
        $currentUser = Auth::user();
    @endphp
<!--
{{$currentUser->name}} {{$currentUser->last_online_at}}
-->
@endif
</html>