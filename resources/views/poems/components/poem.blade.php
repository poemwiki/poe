<?php

/** @var \App\Models\Poem $poem */
$graphemeLength = max(array_map(function($line) {
  return grapheme_strlen($line);
}, explode("\n", $poem->poem)));


$firstLine = $poem->firstLine;
// TODO @section('keywords', !empty($poem->keywrods) ? $poem->keywrods->join(', ') : '')

?>
<section class="poem" itemscope itemtype="https://schema.org/Article" itemid="{{ $poem->fake_id }}">
  <article>
    <div class="poem-main">
      <div class="flex justify-between items-center mb-6">
        <h1 class="title title-bar font-hei" itemprop="headline" id="title">{{ $poem->title }}</h1>
        <button class="generate-share-img" title="@lang('poem.Share')"
          data-id="{{$poem->id}}" data-title="{{$poem->title}}" data-poet="{{$poem->poetLabel}}"
        >
          {!! file_get_contents(public_path('/images/share.svg')) !!}
        </button>
      </div>

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

      <div class="poem-content {{$graphemeLength >= config('app.length_too_long') ? 'text-justify' : ''}}"
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
        @include('poems.fields.date', [
          'poem' => $poem,
          'class' => 'poem-time'
        ])

        @if($poem->location)
          <dd>{{$poem->location}}</dd>
        @endif

        @include('poems.fields.poet', ['poem' => $poem])
        <br>

        @include('poems.fields.translator', ['poem' => $poem])

        @include('poems.fields.from', ['poem' => $poem])

        @if(in_array($poem->is_owner_uploaded, [\APP\Models\Poem::$OWNER['uploader'], \APP\Models\Poem::$OWNER['translatorUploader']]))
          <dl class="poem-ugc text-sm"><dt title="本作品由{{$poem->is_owner_uploaded === 1 ? '作者' : '译者'}}上传">原创</dt></dl>
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
            <a class="edit btn mt-2"
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

        <ol class="contribution mt-4">
          @php
            /** @var \App\Models\Poem $poem */
            $maxKey = $activityLogs->keys()->max();
            $showFakeInitLog = (count($activityLogs)<1) || ($activityLogs->last()->description !== 'created');
          @endphp
          @foreach($activityLogs as $key=>$log)

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

        <a class="btn create mt-4"
           href="{{ urlOrLoginRef(route('poems/create')) }}">@lang('poem.add poem')</a>

        <dl class="poem-info poem-versions nested-tree text-sm">
          <dt>@lang('poem.Translated/Original Version of This Poem')</dt>
          @include('poems.components.translated', [
              'poem' => $poem->topOriginalPoem,
              'currentPageId' => $poem->id,
              'currentPageOriginalId' => $poem->original_id===$poem->id ? null : $poem->original_id
          ])

          @include('poems.fields.add-translation-button', ['poem' => $poem])
        </dl>
      </dl>
    </section>

  </article>

  @include('poems.components.share')
</section>
