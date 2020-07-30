<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('layouts.icon')

    <title>{{config('app.name')}}</title>

    <!-- Fonts -->
    <link href="{{ asset('css/post.css') }}" rel="stylesheet">
    <script>
        (function(d) {
            var config = {
                    kitId: 'vqw8ekp',
                    scriptTimeout: 3000,
                    async: false
                },
                h=d.documentElement,t=setTimeout(function(){h.className=h.className.replace(/\bwf-loading\b/g,"")+" wf-inactive";},config.scriptTimeout),tk=d.createElement("script"),f=false,s=d.getElementsByTagName("script")[0],a;h.className+=" wf-loading";tk.src='https://use.typekit.net/'+config.kitId+'.js';tk.async=true;tk.onload=tk.onreadystatechange=function(){a=this.readyState;if(f||a&&a!="complete"&&a!="loaded")return;f=true;clearTimeout(t);try{Typekit.load(config)}catch(e){}};s.parentNode.insertBefore(tk,s)
        })(document);
    </script>
</head>
<body>
@if (Route::has('login'))
    <div class="top-right links">
        @auth
            <a href="{{ url('/edit') }}">编辑</a>
        @else
            <a class="site-name" href="{{ route('login') }}">PoemWiki</a>

            {{--                        @if (Route::has('register'))--}}
            {{--                            <a href="{{ route('register') }}">Register</a>--}}
            {{--                        @endif--}}
        @endauth
    </div>
@endif
<div class="position-ref">

    <main class="flex-center">
        @yield('content')
    </main>

</div>
</body>
</html>
