<!DOCTYPE html>
{{-- layouts.app for email verify and confirm page --}}
<html>
<head>
  <meta charset="utf-8">
  <title>{{config('app.name')}}</title>
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta http-equiv="Cache-Control" content="no-transform">
  <meta http-equiv="Cache-Control" content="no-siteapp">
  <meta name="applicable-device"content="pc,mobile">
  @include('layouts.icon')
  @include('layouts.analyze')


  <!-- Styles -->
  <link href="{{ mix('/css/app.css') }}" rel="stylesheet">
  <?php
  $user = Auth::user();
  $inviteCode = $user->invite_code;
  $userName = $user->name;
  ?>
</head>
<body>
<div id="app">
    <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ url('/') }}">
                {{ config('app.name', 'PoemWiki') }}

                <a class="navbar-brand" href="#">
                    <img class="navbar-brand-full" src="/icon/poem-bird.svg" width="30" height="30"
                         alt="PoemWiki Logo">
                </a>
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <!-- Left Side Of Navbar -->
                <ul class="navbar-nav mr-auto">
                </ul>

                <!-- Right Side Of Navbar -->
                <ul class="navbar-nav ml-auto">


                    @if (!\App\Http\Middleware\CheckInviteCode::isInviteCodeLimited($inviteCode) && $user->email_verified_at)
                        <li class="nav-item d-md-down-none mr-4">
                            <button type="button" id="copy"
                                    data-clipboard-text="{{ url('/register?invite_code_from=' . $inviteCode) }}"
                                    data-placement="bottom"
                                    class="text-primary nav-link">@lang('Copy my invite link')</button>
                        </li>
                    @endif

                    <li class="nav-item dropdown">
                        <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                            {{ $userName }} <span class="caret"></span>
                        </a>


                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                            <a class="dropdown-item" href="{{ url('/logout') }}"
                               onclick="event.preventDefault();
                                                 document.getElementById('logout-form').submit();">
                               @lang('Logout')
                            </a>

                            <form id="logout-form" action="{{ url('/logout') }}" method="POST" style="display: none;">
                                @csrf
                            </form>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="sidebar">
        {{--<nav class="sidebar-nav">--}}
            {{--<ul class="nav">--}}
                {{--<li class="nav-item active open">--}}
                    {{--<a class="nav-link active" href="http://pwiki.lol:8881/contents">--}}
                        {{--<i class="fa fa-file-text" aria-hidden="true"></i>--}}
                        {{--<span>诗歌</span>--}}
                    {{--</a>--}}
                {{--</li>--}}
            {{--</ul>--}}
        {{--</nav>--}}
        {{--<button class="sidebar-minimizer brand-minimizer" type="button"></button>--}}
    </div>

    <main>
        @yield('content')
    </main>

    <footer class="app-footer d-none">
        <div class="ml-auto">
            <span>Powered by</span>
            <a href="https://bedtimepoem.com">读首诗再睡觉</a>
        </div>
    </footer>
</div>

</body>
</html>
