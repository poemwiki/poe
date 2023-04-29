<nav class="header-fixed" id="top-nav">
    <ul class="flex-center-vertically">
        <li class="flex-center-vertically site-logo" id="site-logo"><a class="no-bg" href="/"></a></li>
        <li class="flex-center-vertically"><span class="title title-bar no-bg font-hei no-select" id="menu-title">@yield('title')</span></li>
        <li class="flex-center-vertically user-logo">
            @auth
                <a class="menu-button no-bg" href="/me">
                  @if(Auth::user()->is_v)
                    {!! Auth::user()->getVerifiedAvatarHtml() !!}
                  @else
                    <img src="{{Auth::user()->avatarUrl}}" alt="Me">
                  @endif
                </a>
            @else
                <a href="{{ route('login', ['ref' => isset($poem) ? route('p/show', $poem->fake_id, false) : '']) }}" class="menu-button no-bg" alt="Menu button">@lang('Login')</a>
            @endauth
        </li>
        @auth
            <li class="flex-center-vertically search-logo" id="search-logo"><a class="no-bg" href="{{route('q')}}"></a></li>
        @endauth
    </ul>
</nav>