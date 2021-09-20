@extends('layouts.common')

@section('title', $author->label)

@section('keywords', !empty($author->alias_arr) ? $author->alias_arr->join(', ') : '')

@section('author', $author->label)

@section('canonical')<link rel="canonical" href="{{$author->url}}" />@endsection


@push('styles')
  <link href="{{ mix('/css/author.css') }}" rel="stylesheet">
@endpush

@php
$aliasMaxLength = 4;
@endphp

@section('content')
    <article class="poet page">
      <h1 class="text-xl font-bold">{{$author->label}}
        @if($author->wikiData)
          <a class="wikidata-link" href="{{$author->wikiData->wikidata_url}}" target="_blank"></a>
        @endif
      </h1>
      @if(config('app.env') === 'local') {{$author->id}} @endif
      <a class="edit btn"
         href="{{ Auth::check() ? route('author/edit', $author->fakeId) : route('login', ['ref' => route('author/edit', $author->fakeId, false)]) }}">@lang('poem.correct errors or edit')</a>

      <div class="author-relate">
        @if($author->user)
        <div class="avatar-wrapper">{!!$author->user->getVerifiedAvatarHtml()!!}</div>
            <span>此作者页已关联到用户 {{$author->user->name}}{{$lastOnline ? " ($lastOnline 在线)" : ''}}</span>
        @endif
      </div>

      <div class="poet-gallery">
        @if($author->avatar)
          <a href="{{$author->avatar}}" target="_blank"><img class="poet-pic" style="max-width: unset" src="{{$author->avatar}}" alt="avatar of {{$author->name_lang}}"></a>
        @endif
        @if($author->pic_url)
          @foreach($author->pic_url as $url)
              <a href="{{$url}}" target="_blank"><img class="poet-pic" style="max-width: unset" src="{{$url}}" alt="image of {{$author->name_lang}}"></a>
          @endforeach
        @endif
      </div>


      {{--      alias--}}

      @if(!empty($author->alias_arr))
        <div class="poet-brief poet-alias-wrapper">
          <span class="poet-label">@lang('admin.author.columns.alias_arr')：</span>
          <p class="poet-alias">
            @foreach($author->alias_arr as $key=>$alias)
              <a class="poet-alias-item" href="{{route('search', $alias)}}">{{$alias}}</a>
            @endforeach
          </p>
        </div>
      @endif

      @if($author->nation)
        <p class="poet-brief"><span class="poet-label">@lang('admin.author.columns.nation_id')：</span>{{$author->nation->name_lang}}</p>
      @endif

      @if($author->dynasty)
        <p class="poet-brief"><span class="poet-label">@lang('admin.author.columns.dynasty_id')：</span>{{$author->dynasty->name_lang}}</p>
      @endif

{{--  short description  @if($author->wikiData) <p class="poet-brief poet-brief-wikdiata">@lang('wiki.data.desc')：{{$author->wikiData->getDescription(config('app.locale'))}}</p> @endif--}}
      @if($author->wikiData)
        <p class="poet-brief poet-brief-wikdiata"><span class="poet-label">@lang('wiki.pedia.summary')：</span>{{
              t2s($author->wiki_desc_lang ?: $author->fetchWikiDesc())
              }}
          @if($author->wikiData->url)
            <a class="wikipedia-link" href="{{$author->wikiData->url}}" target="_blank"></a>
          @endif
        </p>
      @endif
      <p class="poet-brief" style="white-space: pre-line;"><span class="poet-label">@lang('Introduction')：</span>{{$author->describe_lang}}</p>

        @if($poemsAsPoet->isNotEmpty())
        <h2>@lang("Author's Poem", ['author' => $author->label])</h2>
        @endif

        <ul>
        @foreach($poemsAsPoet as $poem)
            <li class="title-list-item">
                <a class="title title-bar font-song no-bg" href="{{$poem->url}}">{{trim($poem->title) ? trim($poem->title) : '无题'}}</a>
                <a class="first-line no-bg" href="{{$poem->url}}">{!!Str::of($poem->firstLine)->surround('span', function ($i) {
                            return 'style="transition-delay:'.($i*20).'ms"';
                    })!!}</a>
            </li>
        @endforeach
        </ul>

        @if($poemsAsTranslator->isNotEmpty())
        <h2>@lang("Translation Works", ['author' => $author->label])</h2>
        @endif

        <ul>
            @foreach($poemsAsTranslator as $poem)
                <li class="title-list-item">
                    <a class="title title-bar font-song no-bg" href="{{$poem->url}}">{!!
                    Str::of(trim($poem->title) ? trim($poem->title) : '无题')
                        ->surround('span')!!}</a>
                    <a class="first-line no-bg" href="{{$poem->url}}">{!!Str::of($poem->firstLine)->surround('span', function ($i) {
                            return 'style="transition-delay:'.($i*20).'ms"';
                    })!!}<span
                        class="text-gray-400 float-right item-poem-author {{$poem->poetAuthor ? 'poemwiki-link' : ''}}">{{$poem->poetLabel}}</span></a>
                </li>
            @endforeach
        </ul>
    </article>

@endsection


@push('scripts')
    <script src="{{ asset('js/lib/color-hash.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var colorHash = new ColorHash({lightness: 0.6, saturation: 0.86});
            var $titles = document.getElementsByClassName('title');
            for(var item of $titles) {
                item.style.setProperty('--title-color', colorHash.hex(item.innerHTML));
            }
        })

    </script>
@endpush
