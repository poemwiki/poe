<?php

/** @var \App\Models\Poem[] $poems */
if ($poems[0]->poetAuthor) {
    // dd($poem->poetAuthor->nation);
    $nation = $poems[0]->poetAuthor->dynasty ? $poems[0]->poetAuthor->dynasty->name_lang
    : '';
} else {
    $nation = $poems[0]->dynasty
    ? "[$poems[0]->dynasty] "
    : '';
}

$softWrap = true;

$createPageUrl = $poems[0]->topOriginalPoem->is_original ? route('poems/create', ['original_fake_id' => $poems[0]->topOriginalPoem->fake_id], false) : null;

?>
<section class="poem" itemscope itemtype="https://schema.org/Article" itemid="{{ $ids }}">
  <article>
    <div class="poem-main compare">
      @foreach ($poems as $key => $poem)
        <h1 class="title font-hei compare-font-{{$key}}" itemprop="headline" id="title">{{ $poem->title }}<span class="title-version">
            @if($poem->isTranslated)
              @include('poems.fields.translator-names', ['poem' => $poem]) 译
            @else
              {{$poem->poet}}
            @endif
        </span></h1>
      @endforeach

      @if(config('app.env') === 'local')
        @foreach($poems as $key=>$poem)
          <h5 class="compare-bg-{{$key}}">{{$poem->id}}</h5>
        @endforeach
      @endif

      <span itemprops="provider" itemscope itemtype="https://schema.org/Organization" class="hidden">
          <span itemprops="name" style="display: none">PoemWiki</span>
          <meta itemprops="url" content="https://poemwiki.org" />
      </span>

      @foreach($poems as $key=>$poem)
        @if($poem->subtitle)
        <pre class="subtitle font-hei compare-font-{{$key}}" itemprop="subtitle">{{ $poem->subtitle }}</pre>
        @endif
      @endforeach

      @foreach($poems as $key=>$poem)
        @if($poem->preface)
          <pre class="preface font-hei compare-font-{{$key}}" itemprop="preface">{{ $poem->preface }}</pre>
        @endif
      @endforeach

      <div class="poem-content {{$softWrap ? 'soft-wrap' : ''}} text-justify"
           itemprop="articleBody"
           @if($poem->lang) lang="{{ $poem->lang->locale }}" @endif
      >
        <code class="poem-line @if($poem->subtitle) poem-line-empty @else no-height @endif"><br></code>
        {{-- output line 0 of each poem, then line 1, etc. --}}
        @foreach($compareLines as $lineNum => $lines)
          <div class="compare-line" data-line-num="{{$lineNum+1}}">
          @foreach($lines as $key => $lineOfPoem)
            <?php
              $translators = $poems[$key]->isTranslated ? $poems[$key]->translatorsStr : $poems[$key]->poet;
            ?>
            <div class="poem-line-wrapper compare-bg-{{$key}}" data-translators="{{$translators}}">
            @if(trim($lineOfPoem))
              <pre class="poem-line font-hei">{{$lineOfPoem}}</pre>
            @else
              <pre class="poem-line poem-line-empty"><br></pre>
            @endif
            </div>
          @endforeach
          </div>
        @endforeach
        <p class="poem-line no-height"><br></p>
      </div>
    </div>


    <section class="poem-meta">
      <dl class="poem-info">

        @include('poems.fields.date', ['poem' => $poems[0]])

        @if($poem->location)
          <dd>{{$poem->location}}</dd>
        @endif

        @include('poems.fields.poet', ['poem' => $poems[0]])<br>

        @include('poems.fields.from', ['poem' => $poems[0]])

{{--        <a class="btn share" id="share"--}}
{{--           href="{{ route('poems/share', ['fakeId' => $poem->fakeId]) }}">@lang('poem.Share')</a>--}}

        <dl class="poem-info poem-versions nested-tree compare">
          @include('poems.components.translated', [
                    'poem' => $poems[0]->topOriginalPoem,
                    'currentPageId' => $poems[0]->id,
                    'currentPageOriginalId' => $poems[0]->original_id===$poems[0]->id ? null : $poems[0]->original_id
                ])

          @include('poems.fields.add-translation-button', ['poem' => $poems[0]])
        </dl>
      </dl>
    </section>

  </article>
</section>
