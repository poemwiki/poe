@extends('layouts.common')

@section('title'){{$keyword ?? ''}} @lang('search.result')@endsection
@section('author')
  PoemWiki
@endsection


@push('styles')
  <link href="{{ mix('/css/base.css') }}" rel="stylesheet">
  <link href="{{ mix('/css/search.css') }}" rel="stylesheet">
@endpush

@section('content')
  <div class="search page">

    <div class="search-box-wrapper">
      <form class="search-box wiki-form" method="get" action="{{route('query')}}">
        @csrf
        <input type="text" name="keyword" placeholder="@lang('search.Please input keywords here')"
               value="{{$keyword ?? ''}}">
        <button class="btn btn-wire" type="submit">@lang('Search')</button>
      </form>
    </div>


    @if(isset($keyword))
      <h1 class="mt-8 text-lg hidden">@lang('search.result of', ['keyword' => $keyword])</h1>
    @endif


    @if(isset($authors) && isset($poems))
    <p class="text-sm text-gray-400 text-light">@lang('search.count', ['count' => $authors->total() + $poems->total()])</p>
    @endif


    @if(isset($keyword))
      <aside class="mt-4">
        <a class="mr-8" href="{{route('author/create')}}" class="btn">@lang('Add Author') {{$keyword}}</a>
        <a class="mr-8" href="{{route('new')}}" class="btn">@lang('Add Poem') {{$keyword}}</a>
      </aside>
    @endif


    @if(isset($authors) && $authors->total())
      <div class="search-group">
        <h2 class="mt-8 mb-4 text-lg font-bold">@lang('search.result-author')</h2>

        <ol>
          @foreach($authors->items() as $author)
            <li class="group item item-author mb-8 relative">
              @if(isset($author->avatarUrl))
                <img class="item-pic item-left" src="{{$author->avatarUrl}}" alt="{{$author->name_lang}}" />
              @endif
              <div class="item-right">
                <span class="group-hover:text-link inline-block align-text-top font-bold mb-4 text-lg leading-none">{{ $author->name_lang }}</span>
                <p class="item-desc block-with-text leading-normal">{{mb_substr($author->describe_lang, 0, 200)}}</p>
              </div>

              <a class="no-bg block p-0 absolute w-full h-full left-0 top-0" href="{{ $author->url }}"></a>
            </li>
          @endforeach
        </ol>
      </div>
    @endif


    @if(isset($poems) && $poems->total())
      <div class="search-group">
        <h2 class="mt-8 mb-4 text-lg font-bold">@lang('search.result-poem')</h2>

        <ol>

          @foreach($poems->items() as $poem)
            <li class="group item item-poem">
              {{--                    <a class="" href="{{ $item->url }}">{{ $item->title }}</a>--}}
              <div class="item-right title-list-item">
                <a class="title font-song no-bg" target="_blank" href="{{$poem->url}}">{{trim($poem->title) ?: '无题'}}</a>
                <a class="first-line no-bg" target="_blank" href="{{$poem->url}}">{!!Str::of($poem->firstLine)->surround('span', function ($i) {
                            return 'style="transition-delay:'.($i*20).'ms"';
                    })!!}
                  <span
                    class="group-hover:text-link text-gray-400 float-right item-poem-author {{$poem->poetAuthor ? 'poemwiki-link' : ''}}">{{$poem->poetLabel}}</span></a>
              </div>
            </li>
          @endforeach

        </ol>
      </div>
    @endif


    @if(in_array($keyword, ['neruda', 'Neruda', 'Pablo Neruda', '巴勃罗·聂鲁达', '巴勃罗·聶魯達', '聂鲁达', '聶魯達'])))
      <p class="mt-8 text-gray-400 text-xs text-right">“诗歌永远是一种和平的举动。诗歌出自和平，就像面包成于面粉。纵火犯、战犯和豺狼，搜索诗人，为了焚掉他、杀掉他、撕咬他”<br>Pablo Neruda</p>
    @endif
  </div>


  @push('scripts')
    <script src="{{ asset('js/lib/color-hash.js') }}"></script>
    <script>
      // TODO move it to color-hash.blade.php
      document.addEventListener('DOMContentLoaded', function () {
        var colorHash = new ColorHash({lightness: 0.6, saturation: 0.86});
        var $titles = document.getElementsByClassName('title');
        for (var item of $titles) {
          item.style.setProperty('--title-color', colorHash.hex(item.innerHTML));
        }
      })

    </script>
  @endpush

@endsection