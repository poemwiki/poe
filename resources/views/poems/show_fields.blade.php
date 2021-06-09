<?php

/** @var \App\Models\Poem $poem */
if($poem->poetAuthor) {
  // dd($poem->poetAuthor->nation);
  $nation = $poem->poetAuthor->dynasty ? $poem->poetAuthor->dynasty->name_lang
    : ($poem->poetAuthor->nation
        ? $poem->poetAuthor->nation->name_lang
        : ''
      );
} else {
  $nation = $poem->dynasty
    ? "[$poem->dynasty] "
    : ($poem->nation ? "[$poem->nation]" : '');
}


$poetName = '';
if($poem->poet_cn) {
    $poetName = $poem->poet_cn;
    if ($poem->poet_cn !== $poem->poet) {
        $poetName .= ' ('.$poem->poet.')';
    }
} else {
    $poetName = $poem->poet;
}

$graphemeLength = max(array_map(function($line) {
    return grapheme_strlen($line);
}, explode("\n", $poem->poem)));

// TODO 默认情况下不换行，且保留行首空格，$graphemeLength >= $maxLength 时启用soft-wrap
$softWrap = false;
// $maxLengthConf = config('app.language_line_length_max');
// if ($poem->language_id && isset($maxLengthConf[$poem->language_id])) {
//     $maxLength = $maxLengthConf[$poem->language_id];
// } else {
//     $maxLength = config('app.default_soft_wrap_length');
// }
// $softWrap = $softWrap && ($graphemeLength >= $maxLength);

/** @var String $fakeId */
$createPageUrl = $poem->is_original ? route('poems/create', ['original_fake_id' => $fakeId], false) : null;

$firstLine = Str::of($poem->poem)->firstLine();
$cover = $poem->wx->get(0) ? $poem->wx->get(0)->cover_src : 'https://poemwiki.org/icon/apple-touch-icon.png'
// TODO @section('keywords', !empty($poem->keywrods) ? $poem->keywrods->join(', ') : '')
?>

@section('canonical')<link rel="canonical" href="{{$poem->url}}" />@endsection
@section('title'){{$poem->title}}@endsection
@section('author'){{$poem->poet.($poem->poet ? ',' : '').$poem->poet_cn}}@endsection
@section('meta-og')
    <meta property="og:title" content="{{$poem->title}}" />
    <meta property="og:url" content="{{$poem->url}}" />
    <meta property="og:image" content="{{$cover}}" />
    <meta property="og:description" content="{{$firstLine}}" />
    <meta property="og:site_name" content="PoemWiki 诗歌维基" />
    <meta property="og:type" content="article" />
    <meta property="og:article:author" content="" />


    <meta property="twitter:card" content="summary" />
    <meta property="twitter:image" content="{{$cover}}" />
    <meta property="twitter:title" content="{{$poem->title}}" />
    <meta property="twitter:creator" content="{{$poem->poet}}" />
    <meta property="twitter:site" content="PoemWiki 诗歌维基" />
    <meta property="twitter:description" content="{{$firstLine}}" />
@endsection


    <section class="poem" itemscope itemtype="https://schema.org/Article" itemid="{{ $poem->fake_id }}">
        <article>
            <div class="poem-main">
                <h1 class="title title-bar font-hei" itemprop="headline" id="title">{{ $poem->title }}</h1>

                @if(config('app.env') === 'local') <h5>{{$poem->id}}</h5> @endif

                <span itemprops="provider" itemscope itemtype="https://schema.org/Organization" class="hidden">
                    <span itemprops="name">PoemWiki</span>
                    <meta itemprops="url" content="https://poemwiki.org" />
                </span>

                @if($poem->subtitle)
                  <code class="subtitle font-hei" itemprop="subtitle">{{ $poem->subtitle }}</code>
                @endif

                @if($poem->preface)
                    <code class="preface font-hei" itemprop="preface">{{ $poem->preface }}</code>
                @endif

                <div class="poem-content {{$softWrap ? 'soft-wrap' : ''}} {{$graphemeLength >= config('app.length_too_long') ? 'text-justify' : ''}}"
                     itemprop="articleBody"
                     @if($poem->lang) lang="{{ $poem->lang->locale }}" @endif
                >
                    <code class="poem-line @if($poem->subtitle) poem-line-empty @else no-height @endif"><br></code>
                    @foreach(Str::of($poem->poem)->toLines() as $line)
                        @if(trim($line))
                            <code class="poem-line font-hei">{{$line}}</code>
                        @else
                            <code class="poem-line poem-line-empty"><br></code>
                        @endif
                    @endforeach
                    <p class="poem-line no-height"><br></p>
                </div>
            </div>


            <section class="poem-meta">
                <dl class="poem-info">

                    @if($poem->year or $poem->month)
                        @if($poem->year && $poem->month && $poem->date)
                            <dd itemprop="dateCreated" class="poem-time">{{$poem->year}}.{{$poem->month}}.{{$poem->date}}</dd>
                        @elseif($poem->year && $poem->month)
                            <dd itemprop="dateCreated" class="poem-time">{{$poem->year}}.{{$poem->month}}</dd>
                        @elseif($poem->month && $poem->date)
                            <dd itemprop="dateCreated" class="poem-time">{{$poem->month}}.{{$poem->date}}</dd>
                        @elseif($poem->year)
                            <dd itemprop="dateCreated" class="poem-time">{{$poem->year}}</dd>
                        @endif
                    @endif

                    @if($poem->location)
                        <dd>{{$poem->location}}</dd>
                    @endif

                    <dt>@lang('admin.poem.columns.poet')</dt>
                    <dd itemscope itemtype="https://schema.org/Person">@if($nation)<span itemprop="nationality"
                        class="poem-nation">{{$nation}}</span>@endif
                        <address itemprop="name" class="poem-writer">
                            @if($poem->poetAuthor)
                              <a href="{{route('author/show',  ['fakeId' => $poem->poetAuthor->fakeId, 'from' => $poem->id])}}" class="poemwiki-link">{{$poem->poetLabel}}</a>
                            @else
                              <a href="{{route('search', $poetName)}}" class="search-link">{{$poetName}}</a>
                            @endif
                        </address>
                    </dd><br>

                    @if($poem->translatorLabel)
                        <dt>@lang('admin.poem.columns.translator')</dt>
                        <dd itemprop="translator" class="poem-translator">
                        @if($poem->translatorAuthor)
                            <a href="{{route('author/show', ['fakeId' => $poem->translatorAuthor->fakeId])}}" class="poemwiki-link">{{$poem->translatorLabel}}</a>
                        @else
                            <a href="{{route('search', $poem->translator)}}" class="search-link">{{$poem->translator}}</a>
                        @endif
                        </dd><br>
                    @endif

                    @if($poem->from)
                        <dt>@lang('admin.poem.columns.from')</dt>
                        <dd itemprop="isPartOf" class="poem-from">{{$poem->from}}</dd><br>
                    @endif
                </dl>

                @auth
                  @if($poem->is_owner_uploaded)
                    <dl class="poem-ugc"><dt>原创诗歌</dt></dl>
                  @endif

                  @if(!$poem->is_owner_uploaded
                        or ($poem->is_owner_uploaded===App\Models\Poem::$OWNER['uploader'] && Auth::user()->id === $poem->upload_user_id)
                  )
                    <a class="edit btn"
                      href="{{ route('poems/edit', $fakeId) }}">@lang('poem.correct errors or edit')</a>
                  @endif
                @endauth

                @guest
                  @if(!$poem->is_owner_uploaded)
                    <a class="edit btn"
                       href="{{ route('login', ['ref' => route('poems/edit', $fakeId, false)]) }}">@lang('poem.correct errors or edit')</a>
                  @else
                  <dl class="poem-ugc"><dt>原创诗歌</dt></dl>
                  @endif
                @endguest

                <ol class="contribution">
                  @php
                    $maxKey = $poem->activityLogs->keys()->max();
                    $showFakeInitLog = (count($poem->activityLogs)<1) || ($poem->activityLogs->last()->description !== 'created');
                  @endphp
                  @foreach($poem->activityLogs as $key=>$log)

                    @if($key===0 or $key===$maxKey)

                      @if($log->description === 'updated' && $key===0)
                        <li title="{{$log->created_at}}"><a
                            href="{{route('poems/contribution', $fakeId)}}">@lang('poem.latest update'){{get_causer_name($log)}}</a></li>

                      @elseif($log->description === 'created')
                        <li title="{{$log->created_at}}"><a
                            href="{{route('poems/contribution', $fakeId)}}">@lang('poem.initial upload'){{get_causer_name($log)}}</a></li>
                      @endif

                    @endif

                  @endforeach

                  <!-- for poems imported from bedtimepoem, they have no "created" log -->
                  @if($showFakeInitLog)
                    <li title="{{$poem->created_at}}"><a
                          href="{{route('poems/contribution', $fakeId)}}">@lang('poem.initial upload')PoemWiki</a></li>
                  @endif

                </ol>
                <a class="btn create"
                   href="{{ Auth::check() ? route('poems/create') : route('login', ['ref' => route('poems/create')]) }}">@lang('poem.add poem')</a>


                <dl class="poem-info poem-versions nested-tree">
                  <dt>@lang('poem.Translated/Original Version of This Poem')</dt>
                  @include('poems.components.translated', [
                            'poem' => $poem->topOriginalPoem,
                            'currentPageId' => $poem->id,
                            'currentPageOriginalId' => $poem->original_id
                        ])

                  @if($poem->is_original)
                    <dt><a class="btn"
                           href="{{ Auth::check() ? $createPageUrl : route('login', ['ref' => $createPageUrl]) }}">@lang('poem.add another translated version')</a>
                    </dt>
                  @elseif(!$poem->originalPoem)
                    <dt>@lang('poem.no original work related')</dt>
                    <dd><a class="" href="{{ Auth::check() ? route('poems/create', ['translated_fake_id' => $fakeId]) : route('login', ['ref' => route('poems/create', ['translated_fake_id' => $fakeId], false)]) }}">
                        @lang('poem.add original work')</a></dd><br>
                  @endif
                </dl>

            </section>

        </article>
    </section>


    @livewire('score', [
    'poem' => $poem
    ])

    <nav class="next">
        <span>@lang('Next Poem')</span>
        <p>
            <a class="no-bg title font-hei no-select title-bar" href="{{$randomPoemUrl}}">{{$randomPoemTitle}}</a>
            <a class="first-line no-bg" href="{{$randomPoemUrl}}">{!!
                Str::of($randomPoemFirstLine)->surround('span', function ($i) {
                    return 'style="transition-delay:'.($i*20).'ms"';
                })!!}
            </a>
        </p>
    </nav>

@push('scripts')
<script src="{{ asset('/js/lib/color-hash.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var colorHash = new ColorHash({lightness: 0.6, saturation: 0.86});
    var mainColor = colorHash.hex(document.querySelector('article .title').innerText);
    var mainColorNext = colorHash.hex(document.querySelector('.next .title').innerText);
    var $body = document.getElementsByTagName("body")[0];
    $body.style.setProperty('--main-color', mainColor);
    $body.style.setProperty('--main-color-next', mainColorNext);

    var $nav = document.getElementById('top-nav');
    window.addEventListener('scroll', function(e) {
        if(window.scrollY >= 60) {
            $nav.classList.add('show-title');
        } else {
            $nav.classList.remove('show-title');
        }
    });
    $nav.addEventListener('dbclick', function () {
        window.scrollTo({top:0});
    });


  $body.addEventListener('copy', function (e) {
    if (typeof window.getSelection == "undefined") return; //IE8 or earlier...

    var selection = window.getSelection();

    //if the selection is short let's not annoy our users
    if (("" + selection).length < 10) return;

    //create a div outside of the visible area
    var newdiv = document.createElement('div');
    newdiv.style.position = 'absolute';
    newdiv.style.left = '-99999px';
    $body.appendChild(newdiv);
    newdiv.appendChild(selection.getRangeAt(0).cloneContents());

    //we need a <pre> tag workaround
    //otherwise the text inside "pre" loses all the line breaks!
    if (selection.getRangeAt(0).commonAncestorContainer.nodeName === "PRE") {
      newdiv.innerHTML = "<pre>" + newdiv.innerHTML + "</pre>";
    }

    newdiv.innerHTML += "<br /><br /><a href='"
      + '{{$poem->weapp_url ?: $poem->url}}' + "'>"
      + '{{$poem->weapp_url ?: $poem->url}}' + "</a>";

    selection.selectAllChildren(newdiv);
    window.setTimeout(function () { $body.removeChild(newdiv); }, 200);
  });
});

</script>
@endpush