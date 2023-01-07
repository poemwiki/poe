@extends('layouts.fe')
@section('content')
  <?php
  /** @var \App\Models\Poem[] $poems */
  $cover = 'https://poemwiki.org/icon/apple-touch-icon.png';
  $poem = $poems[0];
  ?>
@section('canonical')<link rel="canonical" href="{{$url}}" />@endsection
@section('title'){{$poem->title}}@endsection
@section('author'){{$authors}}@endsection
@section('meta-og')
  <meta property="og:title" content="{{$poem->title}}" />
  <meta property="og:url" content="{{$poem->url}}" />
  <meta property="og:image" content="{{$cover}}" />
  <meta property="og:description" content="{{$poem->firstLine}}" />
  <meta property="og:site_name" content="PoemWiki 诗歌维基" />
  <meta property="og:type" content="article" />
  <meta property="og:article:author" content="{{$authors}}" />


  <meta property="twitter:card" content="summary" />
  <meta property="twitter:image" content="{{$cover}}" />
  <meta property="twitter:title" content="{{$poem->title}}" />
  <meta property="twitter:creator" content="{{$poem->poet}}" />
  <meta property="twitter:site" content="PoemWiki 诗歌维基" />
  <meta property="twitter:description" content="{{$poem->title}} 多版本对照阅读" />
  <link href="{{ mix('/css/compare.css') }}" rel="stylesheet">
@endsection

@include('poems.components.vertical', [
    'poems' => $poems
])

@push('scripts')
@endpush

@endsection
