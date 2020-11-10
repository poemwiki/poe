@extends('layouts.fe')

@section('title'){{$poetName}}@endsection
@section('author')
    PoemWiki
@endsection

@section('content')
    <article class="poet">
        <h1>{{$poetName}}</h1>
        <p>简介：{{$poetDesc}}</p>

        @if($poemsAsPoet->isNotEmpty())
        <h2>
            @if($fromPoetName && $poetName !== $fromPoetName)
                {{$poetName .' ('. $fromPoetName .')'}} 的诗歌
            @else
                {{$poetName}} 的诗歌
            @endif
        </h2>
        @endif
        <ul>
        @foreach($poemsAsPoet as $poem)
            <li>
                <a class="title font-song no-bg" target="_blank" href="{{$poem->url}}">{!!
                    Str::of(trim($poem->title) ? trim($poem->title) : '无题')
                        ->surround('span')!!}</a>
                <p class="first-line">{!!Str::of($poem->poem)->firstLine()->surround('span', function ($i) {
                            return 'style="transition-delay:'.($i*20).'ms"';
                    })!!}</p>
            </li>
        @endforeach
        </ul>

        @if($poemsAsTranslator->isNotEmpty())
        <h2>{{$poetName}} 的译作</h2>
        @endif
        <ul>
            @foreach($poemsAsTranslator as $poem)
                <li>
                    <a class="title font-song no-bg" target="_blank" href="{{$poem->url}}">{!!
                    Str::of(trim($poem->title) ? trim($poem->title) : '无题')
                        ->surround('span')!!}</a>
                    <p class="first-line">{!!Str::of($poem->poem)->firstLine()->surround('span', function ($i) {
                            return 'style="transition-delay:'.($i*20).'ms"';
                    })!!}</p>
                </li>
            @endforeach
        </ul>
    </article>

@endsection


@push('scripts')
    <script src="{{ asset('js/lib/color-hash.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var colorHash = new ColorHash({lightness: 0.6, saturation: 0.86});
            var $titles = document.getElementsByClassName('title');
            for(var item of $titles) {
                item.style.setProperty('--title-color', colorHash.hex(item.innerHTML));
            }
        })

    </script>
@endpush
