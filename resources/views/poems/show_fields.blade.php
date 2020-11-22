<?php

/** @var \App\Models\Poem $poem */
$nation = $poem->dynasty
    ? "[$poem->dynasty] "
    : ($poem->nation ? "[$poem->nation]" : '');

$poetName = '';
if($poem->poet_cn) {
    $poetName = $poem->poet_cn;
    if ($poem->poet_cn !== $poem->poet) {
        $poetName .= '&nbsp;'.$poem->poet;
    }
} else {
    $poetName = $poem->poet;
}
$translator = $poem->translator ? trim($poem->translator) : '';

$graphemeLength = max(array_map(function($line) {
    return grapheme_strlen($line);
}, explode("\n", $poem->poem)));

$softWrap = true;
$maxLengthConf = config('app.language_line_length_max');
if ($poem->language_id) {
    $maxLength = $maxLengthConf[$poem->language_id];
} else {
    $maxLength = config('app.default_soft_wrap_length');
}
$softWrap = $softWrap && ($graphemeLength >= $maxLength);

/** @var String $fakeId */
$createPageUrl = $poem->is_original ? route('poems/create', ['original_fake_id' => $fakeId], false) : null;

$firstLine = Str::of($poem->poem)->firstLine();
$cover = $poem->wx->get(0) ? $poem->wx->get(0)->cover_src : 'https://poemwiki.org/icon/apple-touch-icon.png'
?>
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
                <span itemprops="provider" itemscope itemtype="https://schema.org/Organization" class="hidden">
                    <span itemprops="name">PoemWiki</span>
                    <meta itemprops="url" content="https://poemwiki.org" />
                </span>

                @if($poem->preface)
                    <pre class="preface font-hei" itemprop="preface">{{ $poem->preface }}</pre>
                @endif

                @if($poem->subtitle)
                    <pre class="subtitle font-hei" itemprop="subtitle">{{ $poem->subtitle }}</pre>
                @endif

                <div class="poem-content {{$softWrap ? 'soft-wrap' : ''}} {{$graphemeLength >= config('app.length_too_long') ? 'text-justify' : ''}}"
                     itemprop="articleBody"
                     @if($poem->lang) lang="{{ $poem->lang->locale }}" @endif
                >
                    <code class="poem-line no-height"><br></code>
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
                        <dt>@lang('admin.poem.columns.time')</dt>
                        @if($poem->year && $poem->month && $poem->date)
                            <dd itemprop="dateCreated" class="poem-time">{{$poem->year}}-{{$poem->month}}-{{$poem->date}}</dd><br>
                        @elseif($poem->year && $poem->month)
                            <dd itemprop="dateCreated" class="poem-time">{{$poem->year}}-{{$poem->month}}</dd><br>
                        @elseif($poem->month && $poem->date)
                            <dd itemprop="dateCreated" class="poem-time">{{$poem->month}}-{{$poem->date}}</dd><br>
                        @elseif($poem->year)
                            <dd itemprop="dateCreated" class="poem-time">{{$poem->year}}</dd><br>
                        @endif
                    @endif

                    @if($poem->location)
                        <dt>@lang('admin.poem.columns.location')</dt>
                        <dd>{{$poem->location}}</dd>
                    @endif

                    <dt>@lang('admin.poem.columns.poet')</dt>
                    <dd itemscope itemtype="https://schema.org/Person">@if($nation)<span itemprop="nationality"
                        class="poem-nation">{{$nation}}</span>@endif
                        <address itemprop="name" class="poem-writer">
                            @if($poem->poet_id)
                                <a href="{{route('author/show', ['id' => $poem->poet_id, 'from' => $poem->id])}}">{{$poetName}}</a>
                            @else
                                <a href="{{route('search', $poetName)}}">{{$poetName}}</a>
                            @endif
                        </address>
                    </dd><br>

                    @if($poem->translator)
                        <dt>@lang('admin.poem.columns.translator')</dt>
                        <dd itemprop="translator" class="poem-translator">
                        @if($poem->translator_id)
                            <a href="{{route('author/show', $poem->translator_id)}}">{{$translator}}</a>
                        @else
                            {{$translator}}
                        @endif
                        </dd><br>
                    @endif

                    @if($poem->from)
                        <dt>@lang('admin.poem.columns.from')</dt>
                        <dd itemprop="isPartOf" class="poem-from">{{$poem->from}}</dd><br>
                    @endif
                </dl>
                <a class="edit btn"
                   href="{{ Auth::check() ? route('poems/edit', $fakeId) : route('login', ['ref' => route('poems/edit', $fakeId, false)]) }}">@lang('poem.correct errors or edit')</a>
                <ol class="contribution">
                    @if(count($logs) >= 1)
                        @php
                            //dd($logs);
                            /** @var \App\Models\ActivityLog[] $logs */
                            $latestLog = $logs[0];
                            $initialLog = $logs[count($logs) - 1];
                        @endphp
                        <li title="{{$latestLog->created_at}}"><a
                                href="{{route('poems/contribution', $fakeId)}}">@lang('poem.latest update') {{$latestLog->causer_type === "App\User" ? \App\User::find($latestLog->causer_id)->name : 'PoemWiki'}}</a>
                        </li>
                        <li title="{{$initialLog->created_at}}"><a
                                href="{{route('poems/contribution', $fakeId)}}">@lang('poem.initial upload') {{($initialLog->description === 'created') ? \App\User::find($initialLog->causer_id)->name : 'PoemWiki'}}</a>
                        </li>
                    @else
                        <li title="{{$poem->created_at}}"><a
                                href="{{route('poems/contribution', $fakeId)}}">@lang('poem.initial upload')
                                PoemWiki</a>
                        </li>
                    @endif
                </ol>
                <a class="btn create"
                   href="{{ Auth::check() ? route('poems/create') : route('login', ['ref' => route('poems/create')]) }}">@lang('poem.add poem')</a>

                <dl class="poem-info poem-versions">
                    <dt>@lang('poem.Translated/Original Version of This Poem')</dt>
                    @if(!$poem->is_original)
                        @if(!$poem->originalPoem)
                            <dt>@lang('poem.no original work related')</dt>
                            <dd><a class="" href="{{ Auth::check() ? route('poems/create', ['translated_fake_id' => $fakeId]) : route('login', ['ref' => route('poems/create', ['translated_fake_id' => $fakeId], false)]) }}">
                                @lang('poem.add original work')</a></dd><br>
                        @else
                            <a href="{{$poem->originalPoem->url}}">
                                <dt>{{$poem->originalPoem->lang ? $poem->originalPoem->lang->name_lang.'['.trans('poem.original work').']' : trans('poem.original work')}}</dt>
                                <dd>{{$poem->originalPoem->poet}}</dd><br>
                            </a>
                        @endif

                        @foreach($poem->otherTranslatedPoems()->get() as $t)
                            <a href="{{$t->url}}">
                                <dt>{{$t->lang->name_lang ?? trans('poem.other')}}</dt>
                                <dd>{{$t->translator ?? '佚名'}}</dd><br>
                            </a>
                        @endforeach

                    @elseif($poem->translatedPoems)
                        @foreach($poem->translatedPoems as $t)
                            <a href="{{$t->url}}">
                                <dt>{{$t->lang->name_lang ?? trans('poem.other')}}</dt>
                                <dd>{{$t->translator ?? '佚名'}}</dd><br>
                            </a>
                        @endforeach
                    @endif

                    @if($poem->is_original)
                        <dt><a class="btn"
                               href="{{ Auth::check() ? $createPageUrl : route('login', ['ref' => $createPageUrl]) }}">@lang('poem.add another translated version')</a>
                        </dt>
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
<script src="{{ asset('js/lib/color-hash.js') }}"></script>
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
    $nav.addEventListener('click', function () {
        window.scrollTo({top:0});
    });
});
</script>
@endpush