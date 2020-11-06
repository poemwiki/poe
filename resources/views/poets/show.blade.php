@extends('layouts.fe')

@section('title'){{$poetName}}@endsection
@section('author')
    PoemWiki
@endsection

@section('content')
    <article class="poet">
        <h1 class="hidden">{{$poetName}}</h1>
        <p class="hidden">简介：{{$poetDesc}}</p>


        <h2>{{$poems[0]->poet_cn ? $poems[0]->poet_cn . ($poems[0]->poet_cn === $poems[0]->poet ? '' : '（'.$poems[0]->poet.'）') : $poems[0]->poet}} 的诗歌</h2>
        <ul>
        @foreach($poems as $poem)
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
