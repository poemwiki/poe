<?php

/** @var \App\Models\Poem $poem */
$nation = $poem->dynasty
    ? "[$poem->dynasty] "
    : ($poem->nation ? "[$poem->nation] " : '');
// @TODO poet page url
// $author = '<address><a href="#" itemprop="author" class="poem-writer">' . ($poem->poet_cn ?? $poem->poet) . '</a></address>';
$author = '<address itemprop="author" class="poem-writer">' . ($poem->poet_cn ?? $poem->poet) . '</address>';
$authorLine = $nation . $author;

$translator = $poem->translator ? trim($poem->translator) : '';

$maxLength = max(array_map(function($line) {
    return grapheme_strlen($line);
}, explode("\n", $poem->poem)));
$softWrap = $maxLength >= config('app.soft_wrap_length');

$wxPost = $poem->wx ? $poem->wx->first() : null;

$createPageUrl = $poem->is_original ? route('poems/create', ['original_fake_id' => $fakeId], false) : null;
?>
@section('title'){{$poem->title}}@endsection
@section('author'){{$poem->poet.($poem->poet ? ',' : '').$poem->poet_cn}}@endsection


<section class="poem" itemscope itemtype="http://schema.org/Article" itemid="{{ $poem->url }}">
    <article>
        <h1 class="title font-song no-select" itemprop="name" id="title">{{ $poem->title }}</h1>
        <pre class="poem-content font-song no-select {{$softWrap ? 'soft-wrap' : ''}}" itemprop="poem" lang="{{ $poem->language }}">{{ $poem->poem }}</pre>
        <dl class="poem-info">
            <dt>@lang('admin.poem.columns.poet')</dt><dd>{!!$authorLine!!}</dd>
            @if($poem->translator)
            <dt>@lang('admin.poem.columns.translator')</dt><dd itemprop="translator" class="poem-translator">{{$translator}}</dd>
            @endif
            @if($poem->year)
            <dt>@lang('admin.poem.columns.year')</dt><dd itemprop="dateCreated" class="poem-year">{{$poem->year}}</dd>
            @endif
            @if($poem->from)
            <dt>@lang('admin.poem.columns.from')</dt><dd itemprop="isPartOf" class="poem-from">{{$poem->from}}</dd>
            @endif
        </dl>
        <a class="edit btn" href="{{ Auth::check() ? route('poems/edit', $fakeId) : route('login', ['ref' => route('poems/edit', $fakeId, false)]) }}">@lang('poem.correct errors or edit')</a>
        <a class="btn" href="{{ Auth::check() ? route('poems/create') : route('login', ['ref' => route('poems/create')]) }}">@lang('poem.add poem')</a>
        @if(count($logs) >= 2)
        <ol class="contribution">
            @php
            $latestLog = $logs[0];
            $initialLog = $logs[count($logs) - 1];
            @endphp
            <li title="{{$latestLog->created_at}}">@lang('poem.latest update') {{$latestLog->causer_type === "App\User" ? \App\User::find($latestLog->causer_id)->name : '系统'}}</li>
            <li title="{{$initialLog->created_at}}">@lang('poem.initial upload') {{$initialLog->causer_type === "App\User" ? \App\User::find($initialLog->causer_id)->name : '系统'}}</li>
        </ol>
        @endif

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


@if($poem->bedtime_post_id)
<!-- Bedtime Post Id Field -->
<section class="review">
    <h4>评论
        <a class="add-comment btn no-bg" href="{{ Auth::check() ? route('poems/edit', $fakeId) : route('login', ['ref' => route('poems/edit', $fakeId, false)]) }}" title="编辑"><svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" style="height: 1.4em;
    vertical-align: baseline;" xml:space="preserve"><g><path d="M446.029,0L130.498,267.303l-20.33,66.646c-8.624,7.369-19.857,11.39-32.017,11.391c-4.776,0-9.583-0.622-14.293-1.848
			l-14.438-3.761L0,512l172.268-49.421l-3.759-14.438c-4.454-17.1-0.883-34.137,9.54-46.309l66.648-20.331L512,65.971L446.029,0z
			 M136.351,441.068l-61.413,17.618l42.732-42.732L96.045,394.33l-42.731,42.732l17.627-61.444c2.401,0.202,4.807,0.303,7.21,0.303
			c16.215-0.001,31.518-4.56,44.35-13.043l26.609,26.609C139.202,404.41,134.73,422.458,136.351,441.068z M173.977,371.102
			l-33.079-33.078l10.109-33.14l56.109,56.109L173.977,371.102z M235.003,345.632l-68.636-68.636l46.828-39.671l61.478,61.478
        L235.003,345.632z M236.61,217.492L444.314,41.535l26.152,26.152L294.509,275.391L236.61,217.492z"/></g>
</svg></a>
    </h4>
    <ol>
        @if($wxPost)
            @if($wxPost->link && $wxPost->title)
                <li>读首诗再睡觉公众号：<a target="_blank" href="{{ $wxPost->link }}">{{ $wxPost->title }}</a></li>
            @elseif($wxPost->link)<li><a target="_blank" href="{{ $wxPost->link }}"> 读首诗再睡觉公众号</a></li>
            @endif
        @endif

        @if($poem->bedtime_post_title)<li>读睡博客存档：<a target="_blank" href="https://bedtimepoem.com/archives/{{ $poem->bedtime_post_id }}">{{ $poem->bedtime_post_title }}</a></li>
        @else<li>><a target="_blank" href="https://bedtimepoem.com/archives/{{ $poem->bedtime_post_id }}">读睡博客存档</a></li
        @endif
    </ol>


</section>
@endif


<script src="{{ asset('js/lib/color-hash.js') }}"></script>
<script>
    var colorHash = new ColorHash({lightness: 0.6, saturation: 0.86});
    var mainColor = colorHash.hex('{{ $poem->title }}'); // '#8796c5'
    document.getElementById("title").style.setProperty('--main-color', mainColor);
</script>
