@extends('layouts.common')

@section('title', $label)

@section('keywords', !empty($alias) ? $alias->join(', ') : '')

@section('author', $label)

@section('canonical')
  <link rel="canonical" href="{{$author->url}}"/>
@endsection


@push('styles')
  <link href="{{ mix('/css/base.css') }}" rel="stylesheet">
  <link href="{{ mix('/css/author.css') }}" rel="stylesheet">
@endpush

@php
  $aliasMaxLength = 4;
@endphp

@section('content')
  <article class="poet page">

    <div class="flex items-center">
      @if($author->avatarUrl)
        <img class="w-1/12 mr-2" style="max-width: unset" src="{{$author->avatarUrl}}"
             alt="avatar of {{$author->name_lang}}">
      @endif

      <h1 class="text-xl font-bold">{{$label}}
        @if($author->weappCodeUrl)
          <a class="weapp-code" href="{{$author->weappCodeUrl}}" target="_blank"><img src="{{$author->weappCodeUrl}}"
                                                                                   alt="微信小程序码"></a>
        @endif
        @if($author->wikiData)
          <a class="wikidata-link" href="{{$author->wikiData->wikidata_url}}" target="_blank"></a>
        @endif
      </h1>

      @if(config('app.env') === 'local')
        {{$author->id}}
      @endif
    </div>

    <div class="author-relate mt-4">
      @if($author->user)
        <div class="avatar-wrapper">{!!$author->user->getVerifiedAvatarHtml()!!}</div>
        <span>此作者页已关联到用户 {{$author->user->name}}{{$lastOnline ? " ($lastOnline 在线)" : ''}}</span>
      @endif
    </div>

    <div class="poet-gallery mt-4">
      @if($author->pictures)
        @foreach($author->pictures as $url)
          <a href="{{$url}}" target="_blank"><img class="poet-pic" src="{{$url}}" alt="image of {{$author->name_lang}}"></a>
        @endforeach
      @endif
    </div>

    @if(!empty($alias))
      <div class="poet-alias-wrapper mt-8 leading-loose flex items-baseline">
        <span class="pr-2 font-bold">@lang('admin.author.columns.alias_arr')</span>
        <p class="poet-alias">
          @foreach($alias as $key=>$aliaName)
            <a class="poet-alias-item" href="{{route('search', $aliaName)}}">{{$aliaName}}</a>
          @endforeach
        </p>
      </div>
    @endif

    @if($author->nation)
      <p class="mt-4"><span
          class="pr-2 font-bold">@lang('admin.author.columns.nation_id')</span>{{$author->nation->name_lang}}</p>
    @endif

    @if($author->dynasty)
      <p class="mt-4"><span
          class="pr-2 font-bold">@lang('admin.author.columns.dynasty_id')</span>{{$author->dynasty->name_lang}}</p>
    @endif

    {{--descriptions--}}
    <div class="tabs mt-8 tabs-desc">

      <input type="radio" name="tabs" id="tab-desc-poemwiki" checked="checked">
      <label for="tab-desc-poemwiki">@lang('Introduction')</label>
      <div class="tab">
        <p class="text-justify leading-loose" style="white-space: pre-line; word-break: break-all;">{{$author->describe_lang}}
          @if($author->weappCodeUrl)
            <a class="weapp-code" href="{{$author->weappCodeUrl}}" target="_blank"><img src="{{$author->weappCodeUrl}}"
                                                                                     alt="微信小程序码"></a>
          @endif
        </p>

        <a class="edit btn text-xs"
           href="{{ urlOrLoginRef(route('author/edit', $author->fakeId, false)) }}">@lang('poem.correct errors or edit')</a>
      </div>

      @if($author->wikiData)
        <input type="radio" name="tabs" id="tab-desc-wikipedia">
        <label for="tab-desc-wikipedia">Wikipedia</label>
        <div class="tab">
          <p class="text-justify leading-loose">{{
              t2s($author->wiki_desc_lang ?: $author->fetchWikiDesc())
              }}
          </p>
          @if($author->wikiData->url)
            <a class="wikipedia-link mt-2" href="{{$author->wikiData->url}}"
               target="_blank">{{$author->wikiData->url}}</a>
          @endif
        </div>
      @endif
    </div>


    {{--poems--}}
    <div class="tabs mt-8 tabs-poems">

      <input type="radio" name="poem-tabs" id="tab-author-poem" checked="checked">
      <label class="text-lg" for="tab-author-poem">@lang("Author's Poem", ['author' => $label]) ({{$poemsAsPoet->count()}})</label>

      <div class="tab">
        @if($poemsAsPoet->isNotEmpty())
          <ul>
            @foreach($poemsAsPoet as $poem)
              <li class="title-list-item">
                <a class="title font-song no-bg" href="{{$poem->url}}">{{trim($poem->title) ?: '无题'}}</a>
                <a class="first-line no-bg" href="{{$poem->url}}">{!!Str::of($poem->firstLine)->surround('span', function ($i) {
                        return 'style="transition-delay:'.($i*20).'ms"';
                })!!}
                  @if(!$poem->isTranslated)
                    @include('poems.fields.date', [
                      'poem' => $poem,
                      'class' => 'text-gray-400 float-right item-poem-author'
                    ])
                  @elseif($poem->translatorLabel)
                    <span
                      class="text-gray-400 float-right item-poem-author">@lang('Translated by', ['translator' => $poem->translatorsStr])</span>
                  @endif
                </a>
              </li>
            @endforeach
          </ul>
        @endif

        <a href="{{urlOrLoginRef(route('poems/create', ['author_fake_id' => $author->fakeID], false))}}"
           class="btn btn-wire mt-8">@lang('Add original work by', ['author' => $label])</a>
      </div>

      @if($poemsAsTranslator->isNotEmpty())
        <input type="radio" name="poem-tabs" id="tab-translator-poem">
        <label class="text-lg"
               for="tab-translator-poem">@lang("Translation Works", ['author' => $label]) ({{$poemsAsTranslator->count()}})</label>

        <div class="tab">
          <ul>
            @foreach($poemsAsTranslator as $poem)
              <li class="title-list-item">
                <a class="title font-song no-bg" href="{{$poem->url}}">{!!
            Str::of(trim($poem->title) ? trim($poem->title) : '无题')
                ->surround('span')!!}</a>
                <a class="first-line no-bg" href="{{$poem->url}}">{!!Str::of($poem->firstLine)->surround('span', function ($i) {
                    return 'style="transition-delay:'.($i*20).'ms"';
            })!!}<span
                    class="text-gray-400 float-right item-poem-author {{$poetLabelMap[$poem->id]['author_id'] ? 'poemwiki-link' : ''}}">{{$poetLabelMap[$poem->id]['name']}}</span></a>
              </li>
            @endforeach
          </ul>

          <a href="{{urlOrLoginRef(route('poems/create', ['translator_fake_id' => $author->fakeID], false))}}"
             class="btn btn-wire mt-8">@lang('Add translated work by', ['translator' => $label])</a>
        </div>
      @endif

    </div>

  </article>

@endsection


@push('scripts')
  <script src="{{ asset('js/lib/color-hash.js') }}"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var colorHash = new ColorHash({lightness: 0.6, saturation: 0.86});
      var $titles = document.getElementsByClassName('title');
      for (var item of $titles) {
        item.style.setProperty('--title-color', colorHash.hex(item.innerHTML));
      }
    })

  </script>
@endpush
