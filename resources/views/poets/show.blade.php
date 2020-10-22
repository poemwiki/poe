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
            <li><a target="_blank" href="{{$poem->url}}">{{trim($poem->title) ? trim($poem->title) : '无题'}}</a></li>
        @endforeach
        </ul>
    </article>
@endsection
