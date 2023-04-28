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
  </style>
@endpush

@section('content')
  <div id="app" @scroll.passive="onScroll">
    <div class="page" ref="page">
      <h1 class="text-xl font-bold hidden">赛诗会</h1>

      <section class="mb-16">
        <ul class="min-h-screen flex flex-col justify-center">
          <li class="mb-8" v-for="campaign in campaigns" v-cloak>
            <a class="campaign" target="_blank" :href="'/campaign/' + campaign['id'] + '/poems'">
              <img :src="campaign['image_url']" :alt="campaign['name_lang']" class="w-full inline-block">
            </a>
          </li>
          <li v-if="loading" class="w-full text-center loading-box self-center">
          </li>
        </ul>
      </section>

    </div>

  </div>
@endsection

@push('scripts')
  <script src="{{ mix('/js/campaign.js') }}"></script>
@endpush