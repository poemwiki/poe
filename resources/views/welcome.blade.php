<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="author" content="PoemWiki,诗歌维基">
    <meta name="description" content="PoemWiki">
    <meta name="keyword" content="poemwiki,诗歌维基,poem,poetry,poet,诗,诗歌,诗人">
    @include('layouts.icon')
    @include('layouts.analyze')

    <title>{{config('app.name')}} @lang('poemwiki')</title>

    <!-- Fonts -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

</head>
<body class="position-ref">

@include('layouts.fe-menu')

<div class="flex-center position-ref full-height">


    <div class="content">
      <div class="title m-b-md">
        <a class="no-bg" style="font-size: 46px;" href="{{ $poemUrl }}">诗歌维基</a>
        <br>

        <img class="qr" style="width: 50%" src="https://poemwiki-1254719278.cos.ap-guangzhou.myqcloud.com/img/common/weapp-qr-50.jpg" alt="">
      </div>
    </div>

    <div class="links" style="position:absolute; bottom: 1em;">
      <a class="no-bg" href="{{route('new')}}">上传诗歌</a>
      <a class="no-bg" href="/q">搜索</a>
      <a class="no-bg" href="/page/about">关于</a>
      @auth
        @if(Auth::user()->is_admin)
          <a class="no-bg" href="/calendar">诗歌日历</a>
        @endif
      @endauth
      <a class="no-bg" target="_blank" href="https://bedtimepoem.com">读首诗再睡觉</a>
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