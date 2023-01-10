<!-- render poem $poem url and it's translated poems list -->
@php
$isComparePage = request()->route()->getName() == 'compare';
if(isset($idArr)){
    $canAddCompare = count($idArr) < config('app.max_compare_poem_count');
}
@endphp

<div class="child">

  @php
    /** @var \App\Models\Poem $poem */
    /** @var int $currentPageId */
    /** @var int[] $idArr */
    $children = $poem->translatedPoems()->orderBy('language_id')->get()->filter(function($item) {
        return $item->id !== $item->original_id;
    });

    $childrenCount = $children->count();
    //dd($currentPageId, $children->pluck('id'));
    /** @var int $currentPageOriginalId */
    $isTranslatedFrom = $poem->id === $currentPageOriginalId;
  @endphp

  @if(($poem->id !== $currentPageId or $isComparePage or $childrenCount))
  <a class="translated
     @if($isComparePage) compare-bg-{{array_search($poem->id, $idArr)}} @endif"
     href="{{$poem->url}}"
     @if($isTranslatedFrom) title="@lang('Translated from this version')" @endif
  >
    <dt>
      @if($isTranslatedFrom && !$isComparePage)
        <span class="translated-from">@lang('Translated from')</span>&nbsp;
      @endif
      {{$poem->lang->name_lang ?? trans('unkown language')}}
      {{$poem->is_original ? '['.trans('poem.original work').']' : ''}}
    </dt>

    <dd>
      @include('poems.fields.translator-names', ['poem' => $poem])
      {{$poem->is_original && !$isComparePage ? $poem->title : ''}}
    </dd>

    @if(!$isComparePage)
      @if($poem->id !== $currentPageId)
        <a class="btn-compare btn-compare-add" href="{{route('compare', implode(',', [$currentPageId, $poem->id]))}}">+对照</a>
      @endif
    @else
      @if(array_search($poem->id, $idArr) === false)
        @if($canAddCompare)
          <?php
          $added = array_merge($idArr, [$poem->id]);
          ?>
          <a class="btn-compare btn-compare-add" href="{{route('compare', implode(',', $added))}}">+对照</a>
        @endif
      @elseif(count($idArr) > 2)
        <?php
        $filtered = array_filter($idArr, function($id) use ($poem) {
          return $id !== $poem->id;
        });
        ?>
        <a class="btn-compare btn-compare-remove" href="{{route('compare', implode(',', $filtered))}}">-对照</a>
      @endif
    @endif
  </a>
  @endif

  @if($childrenCount)
    <div class="parent">
      @foreach($children as $translatedPoem)
        @if($translatedPoem->id !== $currentPageId or $isComparePage)
          {{--TODO avoid infinate recusion here.--}}
          @include('poems.components.translated', [
              'poem' => $translatedPoem,
              'currentPageId' => $currentPageId
          ])
        @endif
      @endforeach
    </div>
  @endif
</div>