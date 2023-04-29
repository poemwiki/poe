@extends('layouts.common')

@section('title', '赛诗会')

@push('styles')
  <link href="{{ mix('/css/campaign.css') }}" rel="stylesheet">
  <style>
    #app {
      height: 100%;
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
    #modal {
      width: 100%;
      height: 100%;
      top: 0;
      position: fixed;
      left: 0;
      background: rgb(0 0 0 / 50%);
    }
  </style>
@endpush



@section('content')
  <div id="app" class="page">
    <ul>
      @foreach($poems as $poem)
        <li class="mb-10 hover:bg-gray-100 rounded-lg p-4 pb-8">

          <h2 class="mb-4 text-xl font-bold"><a class="no-underline text-black hover:text-black hover:bg-transparent focus:text-black active:text-black" target="_blank" href="/p/{{$poem->fakeId}}">{{$poem['title']}}
          </a></h2>


          <a class="no-underline text-black hover:text-black hover:bg-transparent focus:text-black active:text-black" target="_blank" href="/p/{{$poem->fakeId}}"><pre class="mb-4 leading-loose">{{$poem['poem']}}</pre></a>

          <div class="text-gray-500 flex justify-between">
            <div class="flex items-center"><img class="rounded-full mr-2 w-10 inline-block" src="{{$poem->poet_avatar}}" alt="avatar">{{$poem->poetLabel}}</div>
            <div class="flex items-center">
              <span>{{date_ago($poem->created_at)}}</span>
              <button class="ml-2 p-2 generate-share-img"
                data-id="{{$poem->id}}"  data-title="{{$poem->title}}" data-poet="{{$poem->poetLabel}}"
              >
                <img src="{{asset('/images/share.svg')}}" alt="share">
              </button>
            </div>
          </div>
        </li>
      @endforeach
    </ul>

    @include('poems.components.share')
  </div>
@endsection
