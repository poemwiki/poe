@extends('layouts.common')

@section('title')@lang('calendar')@endsection
@section('author')
  PoemWiki
@endsection

@push('styles')
  <link href="{{ mix('css/calendar.css') }}" rel="stylesheet">
@endpush

@section('content')

  <div id="calendar-page" class="page">

    <lunar-full-calendar :config="config" ref="calendar" :events="events"
                         @day-click="selected"></lunar-full-calendar>

    <div class="wrapper md:flex md:flex-row md:gap-x-4">
      <section class="birth w-full md:flex-initial" v-cloak v-show="birth">
        <h2 class="mt-8 text-center font-bold">生于@{{month}}月@{{day}}日的诗人</h2>
        <table class="w-full border-collapse border text-sm leading-10">
          <thead>
            <tr class="border">
              <th>诗人</th>
              <th class="text-right">生卒年月</th>
              <th class="text-right">诞辰周年</th>
            </tr>
          </thead>

          <tbody>
            <tr class="border odd:bg-gray-100 hover:bg-blue-100" v-for="poet in birth" :key="poet.id">
              <td :data-id="poet.id">@{{poet.name_cn}}</td>
              <td class="text-right"
                :title="poet.birth_date + '~' + poet.death_date"
              >@{{poet.birth_date | doted}} - @{{poet.death_date | doted}}
              </td>
              <td class="text-right">@{{currentYear - poet.birth_year}}</td>
            </tr>
          </tbody>
        </table>
      </section>

      <section class="death w-full md:flex-initial" v-cloak v-show="death">
        <h2 class="mt-8 text-center font-bold">卒于@{{month}}月@{{day}}日的诗人</h2>
        <table class="mb-8 w-full border-collapse	border text-sm leading-10">
          <thead>
            <tr class="border">
              <th>诗人</th>
              <th class="text-right">生卒年月</th>
              <th class="text-right">忌辰周年</th>
            </tr>
          </thead>

          <tbody>
            <tr class="border odd:bg-gray-100 hover:bg-blue-100" v-for="poet in death" :key="poet.id">
              <td>@{{poet.name_cn}}</td>
              <td class="text-right"
                :title="poet.birth_date + '~' + poet.death_date"
              >@{{poet.birth_date | doted}} - @{{poet.death_date | doted}}
              </td>
              <td class="text-right">@{{currentYear - poet.death_year}}</td>
            </tr>
          </tbody>
        </table>
      </section>
    </div>



    <vue-element-loading :active="loading" spinner="bar-fade-scale" color="#00f"/>
    @push('scripts')
      <script src="{{ mix('js/calendar.js') }}"></script>
    @endpush
  </div>

@endsection