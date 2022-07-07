<?php

/** @var \App\Models\Poem $poem */
if($poem->poetAuthor) {
  // dd($poem->poetAuthor->nation);
  $nation = $poem->poetAuthor->dynasty ? $poem->poetAuthor->dynasty->name_lang
    : '';
} else {
  $nation = $poem->dynasty
    ? "[$poem->dynasty] "
    : '';
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

$createPageUrl = $poem->is_original ? route('poems/create', ['original_fake_id' => $poem->fake_id], false) : null;

$firstLine = $poem->firstLine;
// TODO @section('keywords', !empty($poem->keywrods) ? $poem->keywrods->join(', ') : '')
?>
<section class="poem" itemscope itemtype="https://schema.org/Article" itemid="{{ $poem->fake_id }}">
  <article>
    <div class="poem-main">
      <h1 class="title title-bar font-hei" itemprop="headline" id="title">{{ $poem->title }}</h1>

      @if(config('app.env') === 'local') <h5>{{$poem->id}}</h5> @endif

      <span itemprops="provider" itemscope itemtype="https://schema.org/Organization" class="hidden">
                    <span itemprops="name" style="display: none">PoemWiki</span>
                    <meta itemprops="url" content="https://poemwiki.org" />
                </span>

      @if($poem->subtitle)
        <pre class="subtitle font-hei" itemprop="subtitle">{{ $poem->subtitle }}</pre>
      @endif

      @if($poem->preface)
        <pre class="preface font-hei" itemprop="preface">{{ $poem->preface }}</pre>
      @endif

      <div class="poem-content {{$softWrap ? 'soft-wrap' : ''}} {{$graphemeLength >= config('app.length_too_long') ? 'text-justify' : ''}}"
           itemprop="articleBody"
           @if($poem->lang) lang="{{ $poem->lang->locale }}" @endif
      >
        <code class="poem-line @if($poem->subtitle) poem-line-empty @else no-height @endif"><br></code>
        @foreach(Str::of($poem->poem)->toLines() as $line)
          @if(trim($line))
            <pre class="poem-line font-hei">{{$line}}</pre>
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
              <a href="{{route('author/show',  ['fakeId' => $poem->poetAuthor->fakeId, 'from' => $poem->id])}}" class="poemwiki-link">
                {{$poem->poetLabel}}
              </a>
            @else
              <a href="{{route('search', $poem->poet_label)}}" class="search-link">
                @if($poem->is_owner_uploaded===1)
                  <img class="author-avatar" src="{{$poem->uploader->avatarUrl}}" alt="{{$poem->poetLabel}}">
                @endif
                {{$poem->poet_label}}
              </a>
            @endif
          </address>
        </dd><br>


        @if($poem->translators->count())
          <dt>@lang('admin.poem.columns.translator')</dt>
          <dd itemprop="translator" class="poem-translator">
            @foreach($poem->translators as $translator)
              @if($translator instanceof \App\Models\Author)
                <a href="{{route('author/show', ['fakeId' => $translator->fakeId])}}" class="author-label poemwiki-link">{{$translator->label}}</a>
              @elseif($translator instanceof \App\Models\Entry)
                <a href="{{route('search', $translator->name)}}" class="author-label search-link">{{$translator->name}}</a>
              @endif
            @endforeach
          </dd><br>
        @elseif($poem->translatorLabel)
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
          <dd itemprop="isPartOf" class="poem-from">@if(isValidUrl($poem->from))
              <a href="{{$poem->from}}" target="_blank">{{$poem->from}}<a>
            @else
              {{$poem->from}}
            @endif
          </dd><br>
        @endif

        @if($poem->flag & \App\Models\Poem::$FLAG['infoNeedConfirm'])
          <dl><dt>此条目被标记为：信息有误，待修改。</dt></dl>
        @endif

        @if($poem->flag & \App\Models\Poem::$FLAG['originalNeedConfirm'])
          <dl><dt>此条目被标记为：有争议的原创内容。</dt></dl>
        @endif

        @auth
          @if(!$poem->is_owner_uploaded
                or ($poem->is_owner_uploaded===App\Models\Poem::$OWNER['uploader'] && Auth::user()->id === $poem->upload_user_id)
          )
            <a class="edit btn"
               href="{{ route('poems/edit', $poem->fake_id) }}">@lang('poem.correct errors or edit')</a>
          @endif
          {{--TODO 原创译作修改--}}
        @else
          @if(!$poem->is_owner_uploaded)
            <a class="edit btn"
               href="{{ route('login', ['ref' => route('poems/edit', $poem->fake_id, false)]) }}">@lang('poem.correct errors or edit')</a>
          @endif
        @endauth

{{--    <a class="edit btn" href="#">@lang('反馈')</a>--}}

        @if(in_array($poem->is_owner_uploaded, [\APP\Models\Poem::$OWNER['uploader'], \APP\Models\Poem::$OWNER['translatorUploader']]))
          <dl class="poem-ugc"><dt title="本作品由{{$poem->is_owner_uploaded === 1 ? '作者' : '译者'}}上传">原创</dt></dl>
        @endif

        <ol class="contribution">
          @php
            /** @var \App\Models\Poem $poem */
            $maxKey = $poem->activityLogs->keys()->max();
            $showFakeInitLog = (count($poem->activityLogs)<1) || ($poem->activityLogs->last()->description !== 'created');
          @endphp
          @foreach($poem->activityLogs as $key=>$log)

            @if($key===0 or $key===$maxKey)

              @if($log->description === 'updated' && $key===0)
                <li title="{{$log->created_at}}"><a
                    href="{{route('poems/contribution', $poem->fake_id)}}">@lang('poem.latest update'){{get_causer_name($log)}}</a></li>

              @elseif($log->description === 'created')
                <li title="{{$log->created_at}}"><a
                    href="{{route('poems/contribution', $poem->fake_id)}}">@lang('poem.initial upload'){{get_causer_name($log)}}</a></li>
              @endif

            @endif

          @endforeach

        <!-- for poems imported from bedtimepoem, they have no "created" log -->
          @if($showFakeInitLog)
            <li title="{{$poem->created_at}}"><a
                href="{{route('poems/contribution', $poem->fake_id)}}">@lang('poem.initial upload')PoemWiki</a></li>
          @endif
        </ol>

        <a class="btn create"
           href="{{ Auth::check() ? route('poems/create') : route('login', ['ref' => route('poems/create')]) }}">@lang('poem.add poem')</a>

        <dl class="poem-info poem-versions nested-tree">
          <dt>@lang('poem.Translated/Original Version of This Poem')</dt>
          @include('poems.components.translated', [
                    'poem' => $poem->topOriginalPoem,
                    'currentPageId' => $poem->id,
                    'currentPageOriginalId' => $poem->original_id===$poem->id ? null : $poem->original_id
                ])

          @if(!$poem->is_translated)
            <dt><a class="btn"
                   href="{{ Auth::check() ? $createPageUrl : route('login', ['ref' => $createPageUrl]) }}">@lang('poem.add another translated version')</a>
            </dt>
          @elseif(!$poem->originalPoem)
            <dt>@lang('poem.no original work related')</dt>
            <dd><a class="" href="{{ Auth::check() ? route('poems/create', ['translated_fake_id' => $poem->fake_id]) : route('login', ['ref' => route('poems/create', ['translated_fake_id' => $poem->fake_id], false)]) }}">
                @lang('poem.add original work')</a></dd><br>
          @endif
        </dl>
      </dl>
    </section>

  </article>
</section>
