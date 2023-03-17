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
      <a class="site-name no-bg" href="{{ $poemUrl }}">诗歌维基</a>
      <br>

      <img class="qr" style="width: 20%" src="<?=cosUrl('/img/common/weapp-qr-50.jpg') ?>" alt="">
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
    <a class="no-bg" href="https://iddpldra3f.larksuite.com/docx/JvqZduINZoPudcxqmtuu07uXsQg" target="_blank">提案与积分</a>
    <a class="no-bg" target="_blank" href="https://bedtimepoem.com">读首诗再睡觉</a>
  </div>
</div>
<style>
  .site-name {
    font-family: source-han-serif-sc, "Songti SC", "Noto Serif CJK SC", "Source Han Serif SC", "Source Han Serif CN", STSong, "AR PL New Sung", "AR PL SungtiL GB", NSimSun, "SimSun", "\5B8B\4F53", "TW\-Sung", "WenQuanYi Bitmap Song", "AR PL UMing CN", "AR PL UMing HK", "AR PL UMing TW", "AR PL UMing TW MBE", PMingLiU, MingLiU, Georgia, "Nimbus Roman No9 L", serif;
    font-weight: 500;
    font-style: normal;
    font-size: 46px;
    /*text-shadow: 0 0 80px rgb(192 219 255 / 75%), 0 0 32px rgb(65 120 255 / 24%);*/
    /*background: linear-gradient(to right, #30CFD0, #c43ad6);*/
    /*-webkit-background-clip: text;*/
    /*-webkit-text-fill-color: transparent;*/
  }
</style>

</body>
</html>