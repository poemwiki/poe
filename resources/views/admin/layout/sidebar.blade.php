<div class="sidebar">
    <nav class="sidebar-nav">
        <ul class="nav">
            <li class="nav-title">{{ trans('brackets/admin-ui::admin.sidebar.content') }}</li>
            <li class="nav-item"><a class="nav-link" href="{{ url('admin/poems') }}"><i class="nav-icon icon-book-open"></i> {{ trans('admin.poem.title') }}</a></li>
            <li class="nav-item"><a class="nav-link" href="{{ url('admin/authors') }}"><i class="nav-icon icon-drop"></i> {{ trans('admin.author.title') }}</a></li>
            <li class="nav-item"><a class="nav-link" href="{{ url('admin/users') }}"><i class="nav-icon icon-globe"></i> {{ trans('admin.user.title') }}</a></li>
            <li class="nav-item"><a class="nav-link" href="{{ url('admin/reviews') }}"><i class="nav-icon icon-puzzle"></i> {{ trans('admin.review.title') }}</a></li>
           <li class="nav-item"><a class="nav-link" href="{{ url('admin/scores') }}"><i class="nav-icon icon-globe"></i> {{ trans('admin.score.title') }}</a></li>
           <li class="nav-item"><a class="nav-link" href="{{ url('admin/genres') }}"><i class="nav-icon icon-ghost"></i> {{ trans('admin.genre.title') }}</a></li>
           <li class="nav-item"><a class="nav-link" href="{{ url('admin/dynasties') }}"><i class="nav-icon icon-diamond"></i> {{ trans('admin.dynasty.title') }}</a></li>
           <li class="nav-item"><a class="nav-link" href="{{ url('admin/nations') }}"><i class="nav-icon icon-graduation"></i> {{ trans('admin.nation.title') }}</a></li>
           <li class="nav-item"><a class="nav-link" href="{{ url('admin/tags') }}"><i class="nav-icon icon-drop"></i> {{ trans('admin.tag.title') }}</a></li>
           <li class="nav-item"><a class="nav-link" href="{{ url('admin/categories') }}"><i class="nav-icon icon-globe"></i> {{ trans('admin.category.title') }}</a></li>
           {{-- Do not delete me :) I'm used for auto-generation menu items --}}

            <li class="nav-title">{{ trans('brackets/admin-ui::admin.sidebar.settings') }}</li>
            <li class="nav-item hidden"><a class="nav-link" href="{{ url('admin/translations') }}"><i class="nav-icon icon-location-pin"></i> {{ __('Translations') }}</a></li>
            <li class="nav-item"><a class="nav-link" href="{{ url('admin/admin-users') }}"><i class="nav-icon icon-user"></i> {{ __('Manage access') }}</a></li>
            {{-- Do not delete me :) I'm also used for auto-generation menu items --}}
            {{--<li class="nav-item"><a class="nav-link" href="{{ url('admin/configuration') }}"><i class="nav-icon icon-settings"></i> {{ __('Configuration') }}</a></li>--}}
        </ul>
    </nav>
    <button class="sidebar-minimizer brand-minimizer" type="button"></button>
</div>
