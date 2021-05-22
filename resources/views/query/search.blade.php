@extends('layouts.common')

@section('title'){{$keyword ?? ''}} @lang('search.result')@endsection
@section('author')
  PoemWiki
@endsection


@push('styles')
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
      <h1>@lang('search.result of', ['keyword' => $keyword])</h1>
    @endif


    @if(isset($authors) && isset($poems))
    <p class="search-count">@lang('search.count', ['count' => count($authors) + count($poems)])</p>
    @endif


    @if(isset($keyword))
      <aside class="">
        <a href="{{route('author/create')}}" class="btn">@lang('Add Author') {{$keyword}}</a>
        <a href="{{route('new')}}" class="btn">@lang('Add Poem') {{$keyword}}</a>
      </aside>
    @endif

    @if(isset($authors) && $authors)
      <div class="search-group">
        <h2>@lang('search.result-author')</h2>

        <ol>
          @foreach($authors as $author)
            <li class="item item-author">
              @if(isset($author->searchable->avatar_url))
                <img class="item-pic item-left" src="{{$author->searchable->avatar_url}}">
              @endif
              <div class="item-right title-list-item">
                <a class="item-link title no-bg" href="{{ $author->url }}">{{ $author->title }}</a>
                <p class="item-desc block-with-text"><a class="item-link no-bg"
                                                        href="{{ $author->url }}">{{mb_substr($author->searchable->describe_lang, 0, 200)}}</a>
                </p>
              </div>
            </li>
          @endforeach

        </ol>
      </div>
    @endif

    @if(isset($poems) && count($poems))
      <div class="search-group">
        <h2>@lang('search.result-poem')</h2>

        <ol>

          @foreach($poems as $poem)
            <li class="item item-poem">
              {{--                    <a class="" href="{{ $item->url }}">{{ $item->title }}</a>--}}
              <div class="item-right title-list-item">
                <a class="item-link title-bar title font-song no-bg" target="_blank" href="{{$poem->url}}">{!!
                    Str::of(trim($poem->title) ?: '无题')
                        ->surround('span')!!}</a>
                <a class="first-line no-bg" target="_blank" href="{{$poem->url}}">{!!$poem->firstLine->surround('span', function ($i) {
                            return 'style="transition-delay:'.($i*20).'ms"';
                    })!!}
                  <span
                    class="text-gray-400 float-right item-poem-author {{$poem->poetAuthor ? 'poemwiki-link' : ''}}">{{$poem->poetLabel}}</span></a>
              </div>
            </li>
          @endforeach

        </ol>
      </div>
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