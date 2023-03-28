@extends('layouts.common')

@section('title', '')


@push('styles')
  <link href="{{ mix('/css/author.css') }}" rel="stylesheet">
@endpush

@section('content')
  <article class="list page">
    <h1 class="text-xl font-bold">{{$user->name}}
    </h1>

      <h2>我收藏的</h2>

    <ul>
      @foreach($poems as $poem)
        <li class="title-list-item">
          <a class="title title-bar font-song no-bg" href="{{$poem->url}}">{{trim($poem->title) ? trim($poem->title) : '无题'}}</a>
          <a class="first-line no-bg" href="{{$poem->url}}">{!!Str::of($poem->firstLine)->surround('span', function ($i) {
                            return 'style="transition-delay:'.($i*20).'ms"';
                    })!!}</a>
        </li>
      @endforeach
    </ul>

  </article>

@endsection


@push('scripts')

@endpush
