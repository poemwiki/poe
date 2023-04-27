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
  </style>
@endpush

@push('scripts')
  <script src="https://cdn.staticfile.org/axios/1.3.6/axios.js"></script>
  <script>
    async function onShare(id) {
      const url = `/api/v1/poem/share/${id}/pure`;
      console.log(url);
      const res = await axios.get(url);
      console.log(res);
      if(res.data.code !== 0) {
        alert('生成图片失败，请稍后再试');
        return;
      }
      const imgUrl = res.data.data.url;
      // open new window
      const newWindow = window.open();
      newWindow.document.write(`<img src="${imgUrl}" />`);
    }
  </script>
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
            <span class="flex">{{date_ago($poem->created_at)}}
            <button class="ml-2" onclick="onShare({{$poem->id}})">
              <svg height="16" id="svg2" version="1.1" width="16" xmlns="http://www.w3.org/2000/svg" xmlns:cc="http://creativecommons.org/ns#" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd" xmlns:svg="http://www.w3.org/2000/svg"><defs id="defs4"/><g id="layer1" transform="translate(0,-1036.3622)"><path d="m -22.410713,-3.3303571 a 2.3660715,2.3660715 0 1 1 -4.732143,0 2.3660715,2.3660715 0 1 1 4.732143,0 z" id="path2985" style="fill:#000000;fill-opacity:1;stroke:none" transform="matrix(0.84528301,0,0,0.84528301,33.943395,1042.1773)"/><path d="m -22.410713,-3.3303571 a 2.3660715,2.3660715 0 1 1 -4.732143,0 2.3660715,2.3660715 0 1 1 4.732143,0 z" id="path2985-1" style="fill:#000000;fill-opacity:1;stroke:none" transform="matrix(0.84528301,0,0,0.84528301,33.943395,1052.1773)"/><path d="m -22.410713,-3.3303571 a 2.3660715,2.3660715 0 1 1 -4.732143,0 2.3660715,2.3660715 0 1 1 4.732143,0 z" id="path2985-1-7" style="fill:#000000;fill-opacity:1;stroke:none" transform="matrix(0.84528301,0,0,0.84528301,23.943395,1047.1773)"/><path d="M 13,3 3,8 13,13" id="path3791" style="fill:none;stroke:#000000;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1" transform="translate(0,1036.3622)"/></g></svg>
            </button></span>
          </div>
        </li>
      @endforeach
    </ul>
  </div>
@endsection
