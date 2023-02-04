@php
$isWeappWebview = App\User::isWeAppWebview();
@endphp
@extends($isWeappWebview ? 'layouts.webview' : 'layouts.fe')

@section('title')用户授权协议@endsection
@section('author')
  PoemWiki
@endsection

@section('content')
  <p>“<b>诗歌维基</b>” 用户协议</p>
@endsection

@push('styles')
  <style>
    main{
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: flex-start;
      padding: 1em;
      text-align: justify;
      line-height: 2em;
    }
    main p {
      width: 100%;
    }
    main .qr, .weapp{
      display: block;
      width: 200px;
      max-width: 60%;
      margin: 0 auto;
    }
    .weapp{
      width: 400px;
      max-width: 80%;
    }
  </style>
@endpush