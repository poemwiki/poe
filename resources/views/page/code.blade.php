@extends('layouts.webview')

@section('title')去读睡群聊聊诗@endsection
@section('author')
  PoemWiki
@endsection

@section('content')
  <img class="code" src="<?=cosUrl('/campaign/dushuijun.jpg')?>" alt="读睡君">

@endsection


@push('styles')
  <style>
    body{
      margin: 0;
      padding: 0;
    }
    main{
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: space-around;
      height: 100vh;
      overflow: hidden;
      line-height: 2em;
    }
    main .code{
      display: block;
      height: 100vh;
    }
  </style>
@endpush