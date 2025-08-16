<?php

$softWrap = true;

/**
 * Get the poet or translator from the translated poems tree,
 * to avoid extra queries to the database.
 * @param array $translatedPoemsTree
 * @param int $poemId
 * @return string|null
 */
function getPoetOrTranslatorFromTree($translatedPoemsTree, $poemId) {
  //  return poet label if the poem is the root of the tree(top original poem)
  if ($translatedPoemsTree['id'] === $poemId) {
    return $translatedPoemsTree['isOriginal'] ? $translatedPoemsTree['poetLabel'] : $translatedPoemsTree['translatorStr'];
  }
  if (isset($translatedPoemsTree['translatedPoems'])) {
    foreach ($translatedPoemsTree['translatedPoems'] as $translatedPoem) {
      $result = getPoetOrTranslatorFromTree($translatedPoem, $poemId);
      if ($result) {
        return $result;
      }
    }
  }
  return null;
}
?>
<section class="poem" itemscope itemtype="https://schema.org/Article" itemid="{{ $ids }}">
  <article>
    <div class="poem-main compare">
      @foreach ($poems as $key => $poem)
        <h1 class="title font-hei compare-font-{{$key}}" itemprop="headline" id="title">{{ $poem->title }}<span class="title-version">
            @if($poem->isTranslated)
              {{getPoetOrTranslatorFromTree($translatedPoemsTree, $poem->id)}} 译
            @else
              {{getPoetOrTranslatorFromTree($translatedPoemsTree, $poem->id)}}
            @endif
        </span></h1>
      @endforeach

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
          <pre class="preface font-hei compare-font-{{$key}}" itemprop="preface">{{ $poem->preface }}</pre><br>
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
          <?php
            $isAllEmpty = $lines->every(function ($line) {
                return trim($line) === '';
            });
          ?>
          @if($isAllEmpty)
            <code class="poem-line poem-line-empty"><br></code>
          @else
            @foreach($lines as $key => $lineOfPoem)
              @if(is_null($lineOfPoem))
                @continue
              @endif
              <?php
              $translators = getPoetOrTranslatorFromTree($translatedPoemsTree, $poems[$key]->id);
              ?>
              <div class="poem-line-wrapper compare-bg-{{$key}}" data-translators="{{$translators}}">
                @if(trim($lineOfPoem))
                  <pre class="poem-line font-hei compare-bg-{{$key}}">{{$lineOfPoem}}</pre>
                @else
                  <pre class="poem-line poem-line-empty"><br></pre>
                @endif
              </div>
            @endforeach
          @endif
          </div>
        @endforeach
        <p class="poem-line no-height"><br></p>
      </div>
    </div>


    <section class="poem-meta">
      <dl class="poem-info">

        @foreach($poems as $key => $poem)
          @include('poems.fields.date', [
            'poem' => $poem,
            'class' => 'compare-font-' . $key,
          ])
        @endforeach

        @foreach($poems as $key => $poem)
          @if($poem->location)
            <dd class="compare-font-{{$key}}">{{$poem->location}}</dd>
          @endif
        @endforeach

        <!-- TODO: add share button -->

        <dl class="poem-info poem-versions nested-tree compare">
          @include('poems.components.translated', [
              'translatedPoemsTree' => $translatedPoemsTree,
              'currentPageId' => $poems[0]->id,
              'currentPageOriginalId' => null
          ])

          @include('poems.fields.add-translation-button', ['poem' => $poems[0]])
        </dl>
      </dl>
    </section>

  </article>
</section>
