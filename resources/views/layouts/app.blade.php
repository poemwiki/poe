<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{config('app.name')}}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    @include('layouts.icon')


    <!-- Scripts -->
    <script>
        (function(d) {
            var config = {
                    kitId: 'vqw8ekp',
                    scriptTimeout: 3000,
                    async: true
                },
                h=d.documentElement,t=setTimeout(function(){h.className=h.className.replace(/\bwf-loading\b/g,"")+" wf-inactive";},config.scriptTimeout),tk=d.createElement("script"),f=false,s=d.getElementsByTagName("script")[0],a;h.className+=" wf-loading";tk.src='https://use.typekit.net/'+config.kitId+'.js';tk.async=true;tk.onload=tk.onreadystatechange=function(){a=this.readyState;if(f||a&&a!="complete"&&a!="loaded")return;f=true;clearTimeout(t);try{Typekit.load(config)}catch(e){}};s.parentNode.insertBefore(tk,s)
        })(document);
    </script>
    <script src="{{ asset('js/app.js') }}" defer></script>


    <!-- Styles -->
    <link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
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
                {{ config('app.name', 'Laravel') }}

                <a class="navbar-brand" href="#">
                    <img class="navbar-brand-full" src="/icon.svg" width="30" height="30"
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
