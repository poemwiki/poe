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
