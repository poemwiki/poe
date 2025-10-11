@extends('layouts.common')

@section('title', $user->name)


@push('styles')
  <link href="{{ mix('/css/base.css') }}" rel="stylesheet">
  <style>
  #app {
    height: 100vh;
    overflow-y: scroll;
  }
  </style>
@endpush

@section('content')
<div>
  <div class="page">
    <span class="hidden" ref="userID">{{$user->id}}</span>
      <div class="flex justify-between mb-4 items-center">
        <h1 class="text-xl font-bold">{{$user->name}}</h1>
        <a class="no-bg h-10 border rounded-lg px-2 py-0 flex items-center text-sm" href="{{route('logout')}}">@lang('Logout')</a>
      </div>

      <!-- Tabs Nav -->
      <ul class="flex border-b mb-8 text-sm select-none">
        <li @click="switchTab('original')" :class="['cursor-pointer text-lg px-4 py-2 -mb-px border-b-2', activeTab==='original' ? 'border-ui text-ui font-bold' : 'border-transparent text-inactive']">
          我的原创
        </li>
        <li @click="switchTab('fiveStar')" :class="['cursor-pointer text-lg px-4 py-2 -mb-px border-b-2', activeTab==='fiveStar' ? 'border-ui text-ui font-bold' : 'border-transparent text-inactive']">
          我的五星
        </li>
        <li @click="switchTab('contribution')" :class="['cursor-pointer text-lg px-4 py-2 -mb-px border-b-2', activeTab==='contribution' ? 'border-ui text-ui font-bold' : 'border-transparent text-inactive']">
          我的贡献
        </li>
      </ul>

      <!-- Original Poems Tab -->
      <section v-show="activeTab==='original'">
        <h2 class="mt-2 mb-4 text-ui"><span v-if="originalPoemsTotal!==null" v-cloak>共 @{{originalPoemsTotal}} 首</span></h2>
        <ul class="min-h-[200px] flex flex-col justify-start">
          <li class="group flex items-center justify-between" v-for="poem in originalPoems" :key="'op-'+poem.id" v-cloak>
            <div class="title-list-item">
              <a class="title font-song no-bg" target="_blank" :href="'/p/'+poem['fake_id']">@{{poem['title']}}</a>
              <a class="first-line no-bg" target="_blank" :href="'/p/'+poem['fake_id']">@{{poem['firstLine']}}</a>
            </div>
            <button type="button"
                    class="no-bg w-16 text-center text-gray-500 hover:text-red-500 text-xs border border-gray-400 hover:border-red-400 rounded px-2 py-0.5 hidden group-hover:inline-block"
                    title="删除"
                    @click.prevent="confirmDelete(poem)">
              删除
            </button>
          </li>
          <loading-box v-if="loading" class-name="h-[200px]" />
          <li v-if="!loading && originalPoems.length===0" class="text-sm text-gray-500 p-4" v-cloak>暂无原创诗歌。</li>
        </ul>
      </section>

      <!-- Five Star Poems Tab -->
      <section v-show="activeTab==='fiveStar'" id="five-star-section">
        <h2 class="mt-2 mb-4 text-ui"><span v-if="fiveStarPoemsTotal!==null" v-cloak>共 @{{fiveStarPoemsTotal}} 首</span></h2>
        <ul class="min-h-[200px] flex flex-col show-first-line" ref="fiveStarList">
          <li class="title-list-item" v-for="poem in fiveStarPoems" :key="'fs-'+poem.id" v-cloak>
            <a class="title font-song no-bg" target="_blank" :href="'/p/'+poem['fake_id']">@{{poem['title']}}</a>
            <a class="first-line no-bg" target="_blank" :href="'/p/'+poem['fake_id']">
              @{{poem['firstLine']}}
              <span class="text-gray-400 float-right item-poem-author">
                @{{poem['poet']}}
              </span>
            </a>
          </li>
          <loading-box v-if="fiveStarLoading" tag="li" :class-name="fiveStarPoems.length ? 'pb-0' : 'pb-0 min-h-[inherit]'" />
          <li v-if="!fiveStarLoading && fiveStarPoems.length===0" class="text-sm text-gray-500 p-4" v-cloak>还没有评分为五星的诗歌。</li>
        </ul>
      </section>

      <!-- Contribution Tab -->
      <section v-show="activeTab==='contribution'">
        <h2 class="mt-2 mb-4 text-ui">
          <span v-if="contributionTotal!==null" v-cloak>@{{contributionTotal}} 次贡献（过去一年）</span>
        </h2>
        <div class="relative min-h-[200px] flex flex-col">
          <loading-box v-if="contributionLoading" class-name="h-[200px]" ></loading-box>
          <calendar-heat v-else class="calendar" :data="contributionChartData" />
        </div>
      </section>

  </div>

  <notifications position="bottom right" :duration="2000" />

</div>
@endsection


@push('scripts')
<script src="{{ mix('/js/me.js') }}"></script>
@endpush
