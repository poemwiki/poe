<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="format-detection" content="telephone=no">
  <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, viewport-fit=cover">
  <meta name="author" content="PoemWiki,@yield('author')">
  <meta name="description" content="PoemWiki">
    @include('layouts.icon')
    @include('layouts.analyze')

    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- TODO translatable suffix --}}
    <title>@yield('title', 'PoemWiki') - {{config('app.name')}}</title>

    <link href="{{ mix('/css/form.css') }}" rel="stylesheet">
    @yield('styles')

</head>

<body class="position-ref">
@include('layouts.fe-menu')
<main>

  <div id="app" :class="{'loading': loading}">
    <div class="modals">
      <v-dialog/>
    </div>
    <div>
      <notifications position="bottom right" :duration="2000" />
    </div>

    @yield('body')
  </div>
</main>


@stack('scripts')

@yield('bottom-scripts')
</body>

</html>
