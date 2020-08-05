<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    @include('layouts.icon')
    @include('layouts.analyze')

    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- TODO translatable suffix --}}
    <title>@yield('title', 'Craftable') - {{ trans('brackets/admin-ui::admin.page_title_suffix') }}</title>

    @include('brackets/admin-ui::admin.partials.main-styles')

    @yield('styles')

</head>

<body class="app header-fixed sidebar-fixed sidebar-lg-show">
<header class="app-header navbar">
    <button class="navbar-toggler sidebar-toggler d-lg-none" type="button" data-toggle="sidebar-show">
        <span class="navbar-toggler-icon"></span>
    </button>
    @if(View::exists('admin.layout.logo'))
        @include('admin.layout.logo')
    @endif
    <ul class="nav navbar-nav ml-auto">
        <li class="nav-item dropdown">
            <a role="button" class="dropdown-toggle nav-link">
                <span>


                    <span class="hidden-md-down">{{ Auth::user()->name }}</span>


                </span>
                <span class="caret"></span>
            </a>
            @if(View::exists('admin.layout.profile-dropdown'))
                @include('admin.layout.profile-dropdown')
            @endif
        </li>
    </ul>
</header>

<div class="app-body">


    <main class="main">

        <div class="container-fluid" id="app" :class="{'loading': loading}">
            <div class="modals">
                <v-dialog/>
            </div>
            <div>
                <notifications position="bottom right" :duration="2000" />
            </div>

            @yield('body')
        </div>
    </main>
</div>

@include('admin.partials.footer')

@include('brackets/admin-ui::admin.partials.wysiwyg-svgs')
@include('brackets/admin-ui::admin.partials.main-bottom-scripts')
@yield('bottom-scripts')
</body>

</html>
