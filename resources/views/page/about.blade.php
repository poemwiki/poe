@extends('layouts.fe')

@section('title')关于诗歌维基@endsection
@section('author')
  PoemWiki
@endsection

@section('content')
  <p><b>PoemWiki 诗歌维基</b><br/>是一个开放的，跨语种的诗歌资料库</p>
@endsection

@push('styles')
  <style>
    main{
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: 90vh;
      padding: 1em;
    }
  </style>
@endpush