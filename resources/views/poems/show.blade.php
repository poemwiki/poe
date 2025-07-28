@extends('layouts.fe')

@section('content')
  <?php
  /** @var Poem $poem */
  $cover = 'https://poemwiki.org/icon/apple-touch-icon.png'
  ?>
@section('canonical')<link rel="canonical" href="{{str_replace('://www.', '://', $poem->url)}}" />@endsection
{{--TODO 支持多语言版本UI，并且在 alternate section 列出诗歌对应语言版本的url 例如： <link rel="alternate" href="{{$poem->getAlternateUrl('en') ?: 'https://en.poemwiki.org/p/'.$poem->fake_id}}" hreflang="en" /> --}}
@section('alternate')<link rel="alternate" href="{{$poem->url}}" hreflang="x-default" />@endsection
@section('title'){{$poem->title}}@endsection
@section('author'){{$poem->poet.($poem->poet ? ',' : '').$poem->poet_cn}}@endsection
@section('meta-og')
  <meta property="og:title" content="{{$poem->title}}" />
  <meta property="og:url" content="{{$poem->url}}" />
  <meta property="og:image" content="{{$cover}}" />
  <meta property="og:description" content="{{$poem->firstLine}}" />
  <meta property="og:site_name" content="PoemWiki 诗歌维基" />
  <meta property="og:type" content="article" />
  <meta property="og:article:author" content="" />


  <meta property="twitter:card" content="summary" />
  <meta property="twitter:image" content="{{$cover}}" />
  <meta property="twitter:title" content="{{$poem->title}}" />
  <meta property="twitter:creator" content="{{$poem->poet}}" />
  <meta property="twitter:site" content="PoemWiki 诗歌维基" />
  <meta property="twitter:description" content="{{$poem->firstLine}}" />
@endsection

@include('poems.components.poem', ['poem' => $poem])

<div>
  @livewire('score', [
  'poem' => $poem
  ])
</div>
<nav class="next">
  <span>@lang('Next Poem')</span>
  <p>
    <a class="no-bg title font-hei no-select title-bar" href="{{$randomPoemUrl}}">{{$randomPoemTitle}}</a>
    <a class="first-line no-bg" href="{{$randomPoemUrl}}">{!!
            Str::of($randomPoemFirstLine)->surround('span', function ($i) {
                return 'style="transition-delay:'.($i*20).'ms"';
            })!!}
    </a>
  </p>
</nav>

@push('styles')
  @livewireStyles
@endpush
@push('scripts')
  @livewireScripts
  @include('poems.components.script')
@endpush
@endsection
