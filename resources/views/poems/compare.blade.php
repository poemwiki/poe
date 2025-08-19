@extends('layouts.fe')
@section('content')
  <?php
  /** @var \App\Models\Poem[] $poems */
  $cover = cosUrl('/img/common/poemwiki-2x.png');
  $poem = $poems[0];
  ?>
@section('canonical')<link rel="canonical" href="{{$url}}" />@endsection
@section('title'){{$poem->title}} 各译本对照阅读@endsection
@section('author'){{$authors}}@endsection
@section('meta-og')
  <meta property="og:title" content="{{$poem->title}}" />
  <meta property="og:url" content="{{$poem->url}}" />
  <meta property="og:image" content="{{$cover}}" />
  <meta property="twitter:description" content="{{$poem->title}} 各译本对照阅读" />
  <meta property="og:site_name" content="PoemWiki 诗歌维基" />
  <meta property="og:type" content="article" />
  <meta property="og:article:author" content="{{$authors}}" />


  <meta property="twitter:card" content="summary" />
  <meta property="twitter:image" content="{{$cover}}" />
  <meta property="twitter:title" content="{{$poem->title}}" />
  <meta property="twitter:creator" content="{{$poem->poet}}" />
  <meta property="twitter:site" content="PoemWiki 诗歌维基" />
  <meta property="twitter:description" content="{{$poem->title}} 各译本对照阅读" />
  <link href="{{ mix('/css/compare.css') }}" rel="stylesheet">
@endsection

@include('poems.components.vertical', [
    'poems' => $poems,
    'compareLines' => $compareLines,
    'translatedPoemsTree' => $translatedPoemsTree
])

@push('scripts')
@endpush

@endsection
