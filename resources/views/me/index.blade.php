@extends('layouts.common')

@section('title', $user->name)


@push('styles')
<link href="{{ mix('/css/author.css') }}" rel="stylesheet">
<style>

</style>
@endpush

@section('content')
  <article class="list page" id="app">
    <h1 class="text-xl font-bold">{{$user->name}}</h1>

    <section class="mb-16">
      <h2 class="mt-5 text-lg font-bold">我收藏的</h2>
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
    </section>

    <section class="mb-16">
      <h2 class="text-lg font-bold mb-4"><span ref="contributionCount"></span>&nbsp;contributions in the last year</h2>
      <calendar-heat class="calendar"
                     :data-fetch="() => fetchContributions({{$user->id}})"
      ></calendar-heat>

    </section>

  </article>

@endsection


@push('scripts')
<script src="{{ mix('/js/me.js') }}"></script>
@endpush
