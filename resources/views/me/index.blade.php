@extends('layouts.common')

@section('title', $user->name)


@push('styles')
  <link href="{{ mix('/css/base.css') }}" rel="stylesheet">
  <link href="{{ mix('/css/author.css') }}" rel="stylesheet">
<style>
#app {
  height: 100vh;
  overflow-y: auto;
}
/*#app::-webkit-scrollbar {*/
/*  display: none;*/
/*}*/
/*#app {*/
/*  -ms-overflow-style: none;  !* IE and Edge *!*/
/*  scrollbar-width: none;  !* Firefox *!*/
/*}*/
</style>
@endpush

@section('content')
<div>
  <div class="page">
    <span class="hidden" ref="userID">{{$user->id}}</span>
    <div class="flex justify-between">
      <h1 class="text-xl font-bold">{{$user->name}}</h1>
      <a class="no-bg h-10 border rounded-lg px-2 py-0 flex items-center text-sm" href="{{route('logout')}}">@lang('Logout')</a>
    </div>

    <section class="mb-16">
      <h2 class="text-lg font-bold mb-4"><span ref="contributionCount"></span>&nbsp;次贡献（过去一年）</h2>
      <calendar-heat class="calendar"
                     :data-fetch="() => fetchContributions({{$user->id}})"
      ></calendar-heat>
    </section>

    <section class="mb-16">
      <h2 class="mt-5 text-lg font-bold">我的原创&nbsp;<span v-cloak>共 @{{originalPoemsTotal}} 首</span></h2>
      <ul class="min-h-screen/4 flex flex-col justify-center">
        <li class="title-list-item" v-for="poem in originalPoems" v-cloak>
          <a class="title title-bar font-song no-bg" target="_blank" :href="'/p/'+poem['fake_id']">@{{poem['title']}}</a>
          <a class="first-line no-bg" target="_blank" :href="'/p/'+poem['fake_id']">@{{poem['firstLine']}}</a>
        </li>
  <loading-box :visible="loading" mode="center" tag="div" />
      </ul>
    </section>


    <section class="mb-16" id="five-star-section">
      <h2 class="mt-5 text-lg font-bold">我的五星诗歌</h2>
      <ul class="min-h-screen/4 flex flex-col" ref="fiveStarList">
        <li class="title-list-item" v-for="poem in fiveStarPoems" :key="'fs-'+poem.id" v-cloak>
          <a class="title title-bar font-song no-bg" target="_blank" :href="'/p/'+poem['fake_id']">@{{poem['title']}}</a>
          <a class="first-line no-bg" target="_blank" :href="'/p/'+poem['fake_id']">@{{poem['firstLine']}}</a>
        </li>
        <loading-box :visible="fiveStarLoading" mode="tail" />
        <li v-if="!fiveStarLoading && fiveStarPoems.length===0" class="text-sm text-gray-500 p-4" v-cloak>还没有评分为五星的诗歌。</li>
      </ul>
    </section>

  </div>

  <notifications position="bottom right" :duration="2000" />

</div>
@endsection


@push('scripts')
<script src="{{ mix('/js/me.js') }}"></script>
@endpush
