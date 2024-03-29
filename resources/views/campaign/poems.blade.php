@extends('layouts.common')

@section('title', '赛诗会')

@push('styles')
  <link href="{{ mix('/css/base.css') }}" rel="stylesheet">
  <style>
    a {
      all: unset;
      cursor: pointer;
      color: inherit;
      text-decoration: none;
    }
    #app {
      height: 100vh;
      overflow-y: auto;
    }
    #app::-webkit-scrollbar {
      display: none;
    }
    #app {
      -ms-overflow-style: none;  /* IE and Edge */
      scrollbar-width: none;  /* Firefox */
    }
    button {
      all: unset;
    }
  </style>
@endpush



@section('content')
  <div class="page">
    <div class="mt-4 mb-8 flex items-center justify-between">
      <a href="/campaign" class="">返回</a>
      <span>共 {{count($poems)}} 首</span>
    </div>
    <ul>
      @foreach($poems as $poem)
        <li class="mb-10 hover:bg-gray-100 rounded-lg p-4 pb-8">

          <h2 class="mb-4 text-xl font-bold"><a class="no-underline text-black hover:text-black hover:bg-transparent focus:text-black active:text-black" target="_blank" href="/p/{{$poem->fakeId}}">{{$poem['title']}}
          </a></h2>

          <a class="no-underline text-black hover:text-black hover:bg-transparent focus:text-black active:text-black" target="_blank" href="/p/{{$poem->fakeId}}"><pre class="mb-4 leading-loose whitespace-pre-wrap">{{$poem['poem']}}</pre></a>

          <div class="text-gray-500 flex justify-between">
            <div class="flex items-center"><img class="rounded-full mr-2 w-10 inline-block" src="{{$poem->poet_avatar}}" alt="avatar">{{$poem->poetLabel}}</div>
            <div class="flex items-center">
              <span>{{date_ago($poem->created_at)}}</span>
              <button class="ml-2 p-2 generate-share-img cursor-pointer"
                data-id="{{$poem->id}}"  data-title="{{$poem->title}}" data-poet="{{$poem->poetLabel}}"
              >
                {!! file_get_contents(public_path('/images/share.svg')) !!}
              </button>
            </div>
          </div>
        </li>
      @endforeach
    </ul>

    @include('poems.components.share')
  </div>
@endsection
