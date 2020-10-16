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


<section class="poem" itemscope itemtype="http://schema.org/Article" itemid="{{ $poem->url }}">
    <article>
        <h1 class="title font-song no-select" itemprop="name" id="title">{{ $poem->title }}</h1>
        <pre class="poem-content font-song no-select {{$softWrap ? 'soft-wrap' : ''}}" itemprop="poem" lang="{{ $poem->language }}">{{ $poem->poem }}</pre>
        <dl class="poem-info">
            @if($poem->year or $poem->month)
                <dt>@lang('admin.poem.columns.time')</dt>
                @if($poem->year && $poem->month && $poem->date)
                    <dd itemprop="dateCreated" class="poem-time">{{$poem->year}}-{{$poem->month}}-{{$poem->date}}</dd>
                @elseif($poem->year && $poem->month)
                    <dd itemprop="dateCreated" class="poem-time">{{$poem->year}}-{{$poem->month}}</dd>
                @elseif($poem->month && $poem->date)
                    <dd itemprop="dateCreated" class="poem-time">{{$poem->month}}-{{$poem->date}}</dd>
                @elseif($poem->year)
                    <dd itemprop="dateCreated" class="poem-time">{{$poem->year}}</dd>
                @endif
            @endif

            <dt>@lang('admin.poem.columns.poet')</dt><dd itemscope itemtype="https://schema.org/Person"><span itemprop="nationality" class="poem-nation">{{$nation}}</span><address itemprop="name" class="poem-writer">
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
            <dt>@lang('admin.poem.columns.translator')</dt><dd itemprop="translator" class="poem-translator">{{$translator}}</dd>
            @endif

            @if($poem->from)
                <dt>@lang('admin.poem.columns.from')</dt><dd itemprop="isPartOf" class="poem-from">{{$poem->from}}</dd>
            @endif
        </dl>
        <a class="edit btn" href="{{ Auth::check() ? route('poems/edit', $fakeId) : route('login', ['ref' => route('poems/edit', $fakeId, false)]) }}">@lang('poem.correct errors or edit')</a>
        <a class="btn" href="{{ Auth::check() ? route('poems/create') : route('login', ['ref' => route('poems/create')]) }}">@lang('poem.add poem')</a>
        <ol class="contribution">
        @if(count($logs) >= 1)
            @php
            //dd($logs);
            $latestLog = $logs[0];
            $initialLog = $logs[count($logs) - 1];
            @endphp
            <li title="{{$latestLog->created_at}}"><a href="{{route('poems/contribution', $fakeId)}}">@lang('poem.latest update') {{$latestLog->causer_type === "App\User" ? \App\User::find($latestLog->causer_id)->name : 'PoemWiki'}}</a></li>
            <li title="{{$initialLog->created_at}}"><a href="{{route('poems/contribution', $fakeId)}}">@lang('poem.initial upload') {{($initialLog->description === 'created') ? \App\User::find($initialLog->causer_id)->name : 'PoemWiki'}}</a></li>
        @else
            <li title="{{$poem->created_at}}"><a href="{{route('poems/contribution', $fakeId)}}">@lang('poem.initial upload') PoemWiki</a></li>
        @endif
        </ol>

        <dl class="poem-info">
            <dt>其他版本</dt>
            @if(!$poem->is_original)
                @if(!$poem->originalPoem)
                    <dt>@lang('poem.no original work related')</dt><dd><a class="" href="{{ Auth::check() ? route('poems/create', ['translated_fake_id' => $fakeId]) : route('login', ['ref' => route('poems/create', ['translated_fake_id' => $fakeId], false)]) }}">@lang('poem.add original work')</a></dd>
                @else
                    <dt><a href="{{$poem->originalPoem->url}}" title="@lang('poem.original work')">{{$poem->originalPoem->lang ? $poem->originalPoem->lang->name.'['.trans('poem.original work').']' : trans('poem.original work')}}</a></dt><dd>{{$poem->originalPoem->poet}}</dd>
                @endif

                @foreach($poem->otherTranslatedPoems()->get() as $t)
                    <dt>{{$t->lang->name ?? trans('poem.')}}</dt><dd><a href="{{$t->url}}">{{$t->translator ?? '佚名'}}</a></dd>
                @endforeach

            @elseif($poem->translatedPoems)
                @foreach($poem->translatedPoems as $t)
                    <dt>{{$t->lang->name ?? trans('poem.')}}</dt><dd><a href="{{$t->url}}">{{$t->translator ?? '佚名'}}</a></dd>
                @endforeach
            @endif

            @if($poem->is_original)
            <dt><a class="btn" href="{{ Auth::check() ? $createPageUrl : route('login', ['ref' => $createPageUrl]) }}">@lang('poem.add another translated version')</a></dt>
            @endif

        </dl>

    </article>
</section>

@livewire('score', [
    'poem' => $poem
])


<script src="{{ asset('js/lib/color-hash.js') }}"></script>
<script>
    var colorHash = new ColorHash({lightness: 0.6, saturation: 0.86});
    var mainColor = colorHash.hex('{{ $poem->title }}'); // '#8796c5'
    document.getElementById("title").style.setProperty('--main-color', mainColor);
</script>
