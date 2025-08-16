<!-- render poem $poem url and it's translated poems list -->
@php
$isComparePage = request()->route()->getName() == 'compare';
if(isset($idArr)){
    $canAddCompare = count($idArr) < config('app.max_compare_poem_count');
}

// Using hierarchical data structure
/** @var array $translatedPoemsTree */
/** @var int $currentPageId */
/** @var int[]|null $idArr */
/** @var int|null $currentPageOriginalId */

$poemId = $translatedPoemsTree['id'];
$poemUrl = $translatedPoemsTree['url'];
$poemLanguage = $translatedPoemsTree['language'];
$poemIsOriginal = $translatedPoemsTree['isOriginal'];
$poemTitle = $translatedPoemsTree['title'];
$poetLabel = $translatedPoemsTree['poetLabel'];
$poemTranslatorStr = $translatedPoemsTree['translatorStr'];
$children = $translatedPoemsTree['translatedPoems'] ?? [];

$isTranslatedFrom = $poemId === $currentPageOriginalId;
$childrenCount = count($children);
@endphp

@if(!$poemIsOriginal or $childrenCount > 0)
<div class="child">
  <a class="translated
     @if($isComparePage) compare-bg-{{array_search($poemId, $idArr)}} @endif"
     href="{{$poemUrl}}"
     @if($isTranslatedFrom) title="@lang('Translated from this version')" @endif
  >
    <dt>
      {{$poemLanguage}}
      {{$poemIsOriginal ? '['.trans('poem.original work').']' : ''}}
    </dt>

    <dd>
      {{$poemTranslatorStr}}
      {{!$isComparePage && $poemIsOriginal ? '“'.$poemTitle.'”' : ''}} {{$poemIsOriginal ? $poetLabel : ''}}
    </dd>

    @if(!$isComparePage)
      @if($poemId !== $currentPageId)
        <a class="btn-compare btn-compare-add" href="{{route('compare', implode(',', [$currentPageId, $poemId]))}}">+对照</a>
      @endif
    @else
      @if(array_search($poemId, $idArr) === false)
        @if($canAddCompare)
          <?php
          $added = array_merge($idArr, [$poemId]);
          ?>
          <a class="btn-compare btn-compare-add" href="{{route('compare', implode(',', $added))}}">+对照</a>
        @endif
      @elseif(count($idArr) > 2)
        <?php
        $filtered = array_filter($idArr, function($id) use ($poemId) {
          return $id !== $poemId;
        });
        ?>
        <a class="btn-compare btn-compare-remove" href="{{route('compare', implode(',', $filtered))}}">-对照</a>
      @endif
    @endif
  </a>

  {{-- Render children recursively --}}
  @if($childrenCount)
    <div class="parent">
      @foreach($children as $childData)
        @include('poems.components.translated', [
            'translatedPoemsTree' => $childData,
            'currentPageId' => $currentPageId,
            'currentPageOriginalId' => $currentPageOriginalId
        ])
      @endforeach
    </div>
  @endif
</div>
@endif