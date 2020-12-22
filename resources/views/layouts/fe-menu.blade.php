<nav class="header-fixed" id="top-nav">
    <ul class="flex-center-vertically">
        <li class="flex-center-vertically site-logo" id="site-logo"><a class="no-bg" href="/"></a></li>
        <li class="flex-center-vertically"><span href="#" class="title title-bar no-bg font-hei no-select" id="menu-title">@yield('title')</span></li>
        <li class="flex-center-vertically user-logo">
            @auth
                <a class="menu-button no-bg logout" href="{{route('logout')}}">@lang('Logout')</a>
                <a class="menu-button no-bg" href="#">
                  @if(Auth::user()->is_v)
                    {!! Auth::user()->getVerifiedAvatarHtml() !!}
                  @else
                    <img src="{{Auth::user()->avatarUrl}}" alt="Me">
                  @endif
                </a>
            @else
                <a href="{{ route('login', ['ref' => isset($poem) ? route('p/show', $poem->fake_id, false) : '']) }}" class="menu-button no-bg" alt="Menu button">@lang('Login')</a>
            @endif
        </li>
        @auth
            <li class="flex-center-vertically search-logo" id="search-logo"><a class="no-bg" href="{{route('q')}}"></a></li>
        @endauth
    </ul>
</nav>

<svg class="hidden" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
    <symbol id="menu-button" viewBox="0 0 68 68" preserveAspectRatio="xMinYMid meet">
        <g id="Artboard" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
            <g id="Group-2" transform="translate(18.0, 26.0)" fill="#000000">
                <rect id="Rectangle" x="0" y="0" width="32" height="4"></rect>
                <rect id="Rectangle" x="0" y="12" width="32" height="4"></rect>
            </g>
        </g>
    </symbol>
</svg>