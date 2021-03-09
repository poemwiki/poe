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
            <input type="text" name="keyword" placeholder="@lang('search.Please input keywords here')" value="{{$keyword ?? ''}}">
            <button class="btn btn-wire" type="submit">@lang('Search')</button>
        </form>
    </div>


    @if(isset($keyword))
    <h1>@lang('search.result of', ['keyword' => $keyword])</h1>
    @endif

    @if(isset($res))
    <p class="search-count">@lang('search.count', ['count' => $res->count()])</p>
    @endif

    @if(isset($keyword))
    <aside class="">
      <a href="{{route('author/create')}}" class="btn">@lang('Add Author') {{$keyword}}</a>
      <a href="{{route('new')}}" class="btn">@lang('Add Poem') {{$keyword}}</a>
    </aside>
    @endif

    @if(isset($res))
    @foreach($res->groupByType() as $type => $modelSearchResults)
    <div class="search-group">
        <h2>@lang('search.result-'.$type)</h2>

        <ol>
        @foreach($modelSearchResults as $item)
            <li class="item item-{{$type}}">
                @if($type === 'author')

                    @if(isset($item->searchable->pic_url[0]))
                    <img class="item-pic item-left" src="{{$item->searchable->pic_url[0]}}">
                    @endif
                    <div class="item-right title-list-item">
                        <a class="item-link title no-bg" href="{{ $item->url }}">{{ $item->title }}</a>
                      <p class="item-desc block-with-text"><a class="item-link no-bg" href="{{ $item->url }}">{{mb_substr($item->searchable->describe_lang, 0, 200)}}</a></p>
                    </div>

                @else
{{--                    <a class="" href="{{ $item->url }}">{{ $item->title }}</a>--}}
                    <div class="item-right title-list-item">
                        <a class="item-link title-bar title font-song no-bg" target="_blank" href="{{$item->url}}">{!!
                    Str::of(trim($item->title) ? trim($item->title) : '无题')
                        ->surround('span')!!}</a>
                        <a class="first-line no-bg" target="_blank" href="{{$item->url}}">{!!$item->searchable->firstLine->surround('span', function ($i) {
                            return 'style="transition-delay:'.($i*20).'ms"';
                    })!!}
                          <span class="text-gray-400 float-right">{{$item->searchable->poet_author ? $item->searchable->poet_author->name_lang : $item->searchable->poet}}</span></a>
                    </div>
                @endif
            </li>
        @endforeach
        </ol>
    </div>
    @endforeach
    @endif

</div>


@push('scripts')
    <script src="{{ asset('js/lib/color-hash.js') }}"></script>
    <script>
        // TODO move it to color-hash.blade.php
        document.addEventListener('DOMContentLoaded', function () {
            var colorHash = new ColorHash({lightness: 0.6, saturation: 0.86});
            var $titles = document.getElementsByClassName('title');
            for(var item of $titles) {
                item.style.setProperty('--title-color', colorHash.hex(item.innerHTML));
            }
        })

    </script>
@endpush

@endsection