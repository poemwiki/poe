






<li class="nav-item {{ Request::is('contents*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('contents.index') }}">
        <i class="fa fa-file-text" aria-hidden="true"></i>
        <span>诗歌</span>
    </a>
</li>
<li class="nav-item {{ Request::is('poems*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('poems.index') }}">
        <i class="nav-icon icon-cursor"></i>
        <span>Poems</span>
    </a>
</li>

<li class="nav-item {{ Request::is('languages*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('languages.index') }}">
        <i class="nav-icon icon-cursor"></i>
        <span>Languages</span>
    </a>
</li>
