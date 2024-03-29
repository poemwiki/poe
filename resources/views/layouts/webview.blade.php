<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Cache-Control" content="no-transform">
    <meta http-equiv="Cache-Control" content="no-siteapp">
    <meta name="applicable-device"content="pc,mobile">
    <meta name="format-detection" content="telephone=no">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="author" content="PoemWiki,@yield('author')">
    <meta name="description" content="PoemWiki">

    <meta name="keyword" content="@yield('keywords'),@yield('title'),@yield('author'),@yield('title') 评论,@yield('title') 诗评,@yield('title') review,poemwiki,poem,poetry,poet,诗,诗歌,诗人,诗评,poem review">
    <meta name="description" content="@yield('author') @yield('title') 诗歌全文,@yield('author') @yield('title') 评论、评分">

    @yield('canonical')
    @yield('alternate')
    @include('layouts.icon')

    @yield('meta-og')

    <title>@yield('title') - {{config('app.name')}}</title>

{{--    <link href="{{ mix('/css/post.css') }}" rel="stylesheet">--}}

    @stack('styles')

    @stack('head-scripts')
</head>
<body class="position-ref">

    <main>@yield('content')</main>

    @stack('scripts')

</body>
</html>