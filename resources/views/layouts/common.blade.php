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

  <meta name="keyword" content="@yield('keywords'),@yield('title'),诗人,@yield('author'),@yield('title') 评论,@yield('title') 诗评,@yield('title') review,poemwiki,poem,poetry,poet,诗,诗歌,诗评,poem review">
  <meta name="description" content="@yield('author') @yield('title') 诗歌全文,@yield('author') @yield('title') 评论、评分">

  @yield('canonical')
  @include('layouts.icon')

  @yield('meta-og')

  <title>@yield('title') - {{config('app.name')}}</title>

  @stack('styles')

  @include('layouts.analyze')

  @stack('head-scripts')
</head>
<body class="relative">
    @include('layouts.fe-menu')
    <div class="main-wrapper relative">
      <main class="absolute">@yield('content')</main>
    </div>

    @stack('scripts')



    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $firstLines = document.querySelectorAll('.first-line');
            if('IntersectionObserver' in window) {
                var options = {root: null, rootMargin: '0px', threshold: [0.9]};
                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.intersectionRatio >= 0.9) {
                            document.body.classList.add('show-first-line');
                        }
                    });
                }, options);
                [].forEach.call($firstLines, function($line) {
                    observer.observe($line);
                });
            }
        });

        function ready() {
          console.log(window.__wxjs_environment === 'miniprogram') // true
        }
        if (!window.WeixinJSBridge || !WeixinJSBridge.invoke) {
          document.addEventListener('WeixinJSBridgeReady', ready, false)
        } else {
          ready()
        }
    </script>
</body>


@if(Auth::check())
    @php
        $currentUser = Auth::user();
    @endphp
    @if($currentUser->id === 1)
      @stack('debug')
    @endif
@endif
</html>