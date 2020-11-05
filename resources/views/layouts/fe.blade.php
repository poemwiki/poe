<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="format-detection" content="telephone=no">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="author" content="PoemWiki,@yield('author')">
    <meta name="description" content="PoemWiki">

    <meta name="keyword" content="@yield('title'),@yield('author'),@yield('title') 评论,@yield('title') 诗评,@yield('title') review,poemwiki,poem,poetry,poet,诗,诗歌,诗人,诗评,poem review">
    <meta name="description" content="@yield('author') @yield('title') 诗歌全文,@yield('author') @yield('title') 评论、评分">

    @include('layouts.icon')

    <title>@yield('title') - {{config('app.name')}}</title>

    <!-- Fonts -->
    <link href="{{ asset('css/post.css') }}" rel="stylesheet">

    @livewireStyles

    @include('layouts.analyze')
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