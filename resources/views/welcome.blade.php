<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="author" content="PoemWiki,诗歌维基">
    <meta name="description" content="PoemWiki">
    <meta name="keyword" content="poemwiki,诗歌维基,poem,poetry,poet,诗,诗歌,诗人">
    @include('layouts.icon')
    @include('layouts.analyze')

    <title>{{config('app.name')}} @lang('poemwiki')</title>

    <!-- Fonts -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    @livewireStyles
</head>
<body class="position-ref">

@include('layouts.fe-menu')

<div class="flex-center position-ref full-height">


    <div class="content">
        <div class="title m-b-md">
            <a class="no-bg" href="{{ $poemUrl }}">PoemWiki</a>
        </div>


        <div class="links">
            <a class="no-bg" href="/#">关于</a>
            <a class="no-bg" target="_blank" href="https://bedtimepoem.com">读首诗再睡觉</a>
        </div>
    </div>
</div>

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