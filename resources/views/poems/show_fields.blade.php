<?php

/** @var \App\Models\Poem $poem */
$nation = $poem->dynasty
    ? "[$poem->dynasty] "
    : ($poem->nation ? "[$poem->nation]" : '');

$translator = $poem->translator ? trim($poem->translator) : '';

$maxLength = max(array_map(function($line) {
    return grapheme_strlen($line);
}, explode("\n", $poem->poem)));
$softWrap = $maxLength >= config('app.soft_wrap_length');


$createPageUrl = $poem->is_original ? route('poems/create', ['original_fake_id' => $fakeId], false) : null;
?>
@section('title'){{$poem->title}}@endsection
@section('author'){{$poem->poet.($poem->poet ? ',' : '').$poem->poet_cn}}@endsection



    <section class="poem" itemscope itemtype="http://schema.org/Article" itemid="{{ $poem->fake_id }}">
        <article>
            <h1 class="title font-song" itemprop="name" id="title">{{ $poem->title }}</h1>
            @if($poem->preface)
                <pre class="preface font-song" itemprop="preface">{{ $poem->preface }}</pre> @endif
            @if($poem->subtitle)
                <pre class="subtitle font-song" itemprop="subtitle">{{ $poem->subtitle }}</pre> @endif
            <pre class="poem-content font-song {{$softWrap ? 'soft-wrap' : ''}}" itemprop="text"
                 lang="{{ $poem->language }}">{{ $poem->poem }}</pre>

            <section class="poem-meta">
                <dl class="poem-info">
                    @if($poem->year or $poem->month)
                        <dt>@lang('admin.poem.columns.time')</dt>
                        @if($poem->year && $poem->month && $poem->date)
                            <dd itemprop="dateCreated" class="poem-time">{{$poem->year}}-{{$poem->month}}
                                -{{$poem->date}}</dd>
                        @elseif($poem->year && $poem->month)
                            <dd itemprop="dateCreated" class="poem-time">{{$poem->year}}-{{$poem->month}}</dd>
                        @elseif($poem->month && $poem->date)
                            <dd itemprop="dateCreated" class="poem-time">{{$poem->month}}-{{$poem->date}}</dd>
                        @elseif($poem->year)
                            <dd itemprop="dateCreated" class="poem-time">{{$poem->year}}</dd>
                        @endif
                    @endif

                    <dt>@lang('admin.poem.columns.poet')</dt>
                    <dd itemscope itemtype="https://schema.org/Person">@if($nation)<span itemprop="nationality"
                                                                                         class="poem-nation">{{$nation}}</span>@endif
                        <address itemprop="name" class="poem-writer">
                            <a href="{{route('poet/show', $poem->poet)}}">
                                @if($poem->poet_cn)
                                    {{$poem->poet_cn}}@if($poem->poet_cn !== $poem->poet)（{{$poem->poet}}）@endif
                                @else
                                    {{$poem->poet}}
                                @endif
                            </a>
                        </address>
                    </dd>

                    @if($poem->translator)
                        <dt>@lang('admin.poem.columns.translator')</dt>
                        <dd itemprop="translator" class="poem-translator">{{$translator}}</dd>
                    @endif

                    @if($poem->from)
                        <dt>@lang('admin.poem.columns.from')</dt>
                        <dd itemprop="isPartOf" class="poem-from">{{$poem->from}}</dd>
                    @endif
                </dl>
                <a class="edit btn"
                   href="{{ Auth::check() ? route('poems/edit', $fakeId) : route('login', ['ref' => route('poems/edit', $fakeId, false)]) }}">@lang('poem.correct errors or edit')</a>
                <ol class="contribution">
                    @if(count($logs) >= 1)
                        @php
                            //dd($logs);
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
                            <dt>@lang('poem.no original work related')</dt><a class=""
                                                                              href="{{ Auth::check() ? route('poems/create', ['translated_fake_id' => $fakeId]) : route('login', ['ref' => route('poems/create', ['translated_fake_id' => $fakeId], false)]) }}">
                                <dd>@lang('poem.add original work')</a></dd>
                        @else
                            <a href="{{$poem->originalPoem->url}}">
                                <dt>{{$poem->originalPoem->lang ? $poem->originalPoem->lang->name.'['.trans('poem.original work').']' : trans('poem.original work')}}</dt>
                                <dd>{{$poem->originalPoem->poet}}</dd>
                            </a>
                        @endif

                        @foreach($poem->otherTranslatedPoems()->get() as $t)
                            <a href="{{$t->url}}">
                                <dt>{{$t->lang->name ?? trans('poem.')}}</dt>
                                <dd>{{$t->translator ?? '佚名'}}</dd>
                            </a>
                        @endforeach

                    @elseif($poem->translatedPoems)
                        @foreach($poem->translatedPoems as $t)
                            <a href="{{$t->url}}">
                                <dt>{{$t->lang->name ?? trans('poem.')}}</dt>
                                <dd>{{$t->translator ?? '佚名'}}</dd>
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
        <a class="no-bg title font-song no-select" href="{{$randomPoemUrl}}">{{$randomPoemTitle}}</a>
        <p class="first-line">{!!Str::of($randomPoemFirstLine)->surround('span')!!}</p>
    </nav>

<script src="{{ asset('js/lib/color-hash.js') }}"></script>
<script>
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
    $nav.addEventListener('dblclick', function () {
        window.scrollTo({top:0});
    })
</script>
