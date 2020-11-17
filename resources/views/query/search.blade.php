@extends('layouts.fe')

@section('title'){{$keyword}} @lang('search.result')@endsection
@section('author')
    PoemWiki
@endsection

@section('content')

<div class="search">

    <div class="search-box-wrapper">
        <form class="search-box" method="get" action="{{route('query')}}">
            @csrf
            <input type="text" name="keyword" placeholder="Search PoemWiki" value="{{$keyword}}">
            <button class="btn btn-wire" type="submit">Search</button>
        </form>
    </div>


    <h1>@lang('search.result of', ['keyword' => $keyword])</h1>

    <p class="search-count">@lang('search.count', ['count' => $res->count()])</p>

    <aside>
        @if(!$authorCount)
            <a href="" class="btn">@lang('Add Author') {{$keyword}}</a>
            <a href="" class="btn">@lang('Add Poem') {{$keyword}}</a>
        @endif
    </aside>
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
                        <p class="item-desc">{{$item->searchable->describe_lang}}</p>
                    </div>

                @else
{{--                    <a class="" href="{{ $item->url }}">{{ $item->title }}</a>--}}
                    <div class="item-right title-list-item">
                        <a class="item-link title-bar title font-song no-bg" target="_blank" href="{{$item->url}}">{!!
                    Str::of(trim($item->title) ? trim($item->title) : '无题')
                        ->surround('span')!!}</a>
                        <a class="first-line no-bg" href="{{$item->url}}">{!!Str::of($item->searchable->poem)->firstLine()->surround('span', function ($i) {
                            return 'style="transition-delay:'.($i*20).'ms"';
                    })!!}</a>
                    </div>
                @endif
            </li>
        @endforeach
        </ol>
    </div>
    @endforeach

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