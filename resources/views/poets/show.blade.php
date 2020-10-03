@extends('layouts.fe')

@section('title'){{$poetName}}@endsection
@section('author')
    PoemWiki
@endsection

@section('content')
    <article class="poet">
        <h1 class="hidden">{{$poetName}}</h1>
        <p class="hidden">简介：{{$poetDesc}}</p>


        <h2>{{$poetName}} 的诗歌</h2>
        <ul>
        @foreach($poems as $poem)
            <li><a target="_blank" href="{{$poem->url}}">{{$poem->title}}</a></li>
        @endforeach
        </ul>
    </article>
@endsection
