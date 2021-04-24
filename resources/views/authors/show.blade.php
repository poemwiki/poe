@extends('layouts.fe')

@section('title'){{$author->name_lang}}@endsection
@section('author')
    PoemWiki
@endsection

@section('content')
    <article class="poet">
      <h1>{{$author->label}}
        @if($author->wikidata_id)
          <a class="wikidata-link" href="{{$author->wikiData->wikidata_url}}" target="_blank"></a>&nbsp;
          @if($author->wikiData->url)
            <a class="wikipedia-link" href="{{$author->wikiData->url}}" target="_blank"></a>
          @endif
        @endif
      </h1>
      <a class="edit btn"
         href="{{ Auth::check() ? route('author/edit', $author->fakeId) : route('login', ['ref' => route('author/edit', $author->fakeId, false)]) }}">@lang('poem.correct errors or edit')</a>

      <div class="author-relate">
        @if($author->user)
        <div class="avatar-wrapper">{!!$author->user->getVerifiedAvatarHtml()!!}</div>
            <span>此作者页已关联到用户 {{$author->user->name}}@PoemWiki</span>
        @endif
      </div>

      <div class="poet-gallery">
        @if($author->pic_url)
        @foreach($author->pic_url as $url)
            <img class="poet-pic" src="{{$url}}" alt="image of {{$author->name_lang}}">
        @endforeach
        @endif
      </div>

      @if($author->nation)
      <p class="poet-brief">@lang('admin.author.columns.nation_id')：{{$author->nation->name_lang}}</p>
      @endif

      @if($author->dynasty)
        <p class="poet-brief">@lang('admin.author.columns.dynasty_id')：{{$author->dynasty->name_lang}}</p>
      @endif

{{--  short description  @if($author->wikiData) <p class="poet-brief poet-brief-wikdiata">@lang('wiki.data.desc')：{{$author->wikiData->getDescription(config('app.locale'))}}</p> @endif--}}
      <p class="poet-brief" style="white-space: pre-line;">@lang('Introduction')：{{$author->describe_lang}}</p>
        @if($author->wikiData) <p class="poet-brief poet-brief-wikdiata">@lang('wiki.pedia.summary')：{{
              $author->wiki_desc_lang ?: $author->fetchWikiDesc()
              }}</p>
        @endif

        @if($poemsAsPoet->isNotEmpty())
        <h2>
              {{$author->label}} 的诗歌
        </h2>
        @endif
        <ul>
        @foreach($poemsAsPoet as $poem)
            <li class="title-list-item">
                <a class="title title-bar font-song no-bg" href="{{$poem->url}}">{!!
                    Str::of(trim($poem->title) ? trim($poem->title) : '无题')
                        ->surround('span')!!}</a>
                <a class="first-line no-bg" href="{{$poem->url}}">{!!Str::of($poem->poem)->firstLine()->surround('span', function ($i) {
                            return 'style="transition-delay:'.($i*20).'ms"';
                    })!!}</a>
            </li>
        @endforeach
        </ul>

        @if($poemsAsTranslator->isNotEmpty())
        <h2>{{$author->label}} 的译作</h2>
        @endif
        <ul>
            @foreach($poemsAsTranslator as $poem)
                <li class="title-list-item">
                    <a class="title title-bar font-song no-bg" href="{{$poem->url}}">{!!
                    Str::of(trim($poem->title) ? trim($poem->title) : '无题')
                        ->surround('span')!!}</a>
                    <a class="first-line no-bg" href="{{$poem->url}}">{!!Str::of($poem->poem)->firstLine()->surround('span', function ($i) {
                            return 'style="transition-delay:'.($i*20).'ms"';
                    })!!}</a>
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
