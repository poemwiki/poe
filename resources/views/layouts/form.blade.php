@extends('layouts.common')

@section('author')
  PoemWiki
@endsection

@push('styles')
  <link href="{{ mix('/css/base.css') }}" rel="stylesheet">
  <link href="{{ mix('/css/form.css') }}" rel="stylesheet">
@endpush

@section('content')


  <div id="app" :class="{'loading': loading}" class="page">
    <div class="modals">
      <v-dialog></v-dialog>
    </div>
    <div>
      <notifications position="bottom right" :duration="3000"></notifications>
    </div>

    @yield('form')
  </div>
@endsection
