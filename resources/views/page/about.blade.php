@extends('layouts.fe')

@section('title')关于诗歌维基@endsection
@section('author')
  PoemWiki
@endsection

@section('content')
  <p><b>PoemWiki（诗歌维基）</b>的宗旨是传播优秀诗歌</p>
  <p><b>PoemWiki（诗歌维基）</b>是一个可评分，可评论，跨语种的诗歌资料库</p>
@endsection

@push('styles')
  <style>
    main{
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: 90vh;
    }
  </style>
@endpush