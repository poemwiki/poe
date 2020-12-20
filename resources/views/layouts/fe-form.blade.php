@extends('layouts.common')

@section('author')
  PoemWiki
@endsection

@push('styles')
  <link href="{{ mix('/css/form.css') }}" rel="stylesheet">
@endpush

@section('content')


  <div id="app" :class="{'loading': loading}" class="page">
    <div class="modals">
      <v-dialog/>
    </div>
    <div>
      <notifications position="bottom right" :duration="2000"/>
    </div>

    @yield('form')

@endsection
