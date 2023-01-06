<!-- poem {{$poem->id}} url and it's translated poems list -->
@php
$isComparePage = request()->route()->getName() == 'compare';
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
  <a href="{{$poem->url}}" @if($isTranslatedFrom) title="@lang('Translated from this version')" @endif>
    <dt>
      @if($isTranslatedFrom)
        <span class="translated-from">@lang('Translated from')</span>&nbsp;
      @endif
      {{$poem->lang->name_lang ?? trans('unkown language')}}
      {{$poem->is_original ? '['.trans('poem.original work').']' : ''}}
    </dt>

    <dd>
      @if($poem->is_original)
        {{$poem->poet_label}}
      @else
        @if($poem->translators->count())
          @foreach($poem->translators as $key => $translator)
            @if($translator instanceof \App\Models\Author)
            {{$translator->label}}@if($key < $poem->translators->count()-1),&nbsp;@endif
            @elseif($translator instanceof \App\Models\Entry)
            {{$translator->name}}@if($key < $poem->translators->count()-1),&nbsp;@endif
            @endif
          @endforeach
        @else
          {{$poem->translator_label ?: trans('No Name')}}
        @endif
      @endif
      {{$poem->is_original && !$isComparePage ? $poem->title : ''}}
    </dd>

    @if(!$isComparePage)
      @if($poem->id !== $currentPageId)
        <a class="btn" style="margin-left: 1em" href="{{route('compare', implode(',', [$currentPageId, $poem->id]))}}">+对照</a>
      @endif
    @else
      @if(array_search($poem->id, $idArr) === false)
        <?php
        $added = array_merge($idArr, [$poem->id]);
        ?>
        <a class="btn" style="margin-left: 1em" href="{{route('compare', implode(',', $added))}}">+对照</a>
      @elseif(count($idArr) > 2)
        <?php
        $filtered = array_filter($idArr, function($id) use ($poem) {
          return $id !== $poem->id;
        });
        ?>
        <a class="btn" style="margin-left: 1em" href="{{route('compare', implode(',', $filtered))}}">-对照</a>
      @endif
    @endif
  </a>
  @endif

  @if($childrenCount)
    <div class="parent">
      @foreach($children as $translatedPoem)
        {{$translatedPoem->id .' '. $currentPageId}}
        {{--show all children if it is compare page--}}
        @if($translatedPoem->id !== $currentPageId or $isComparePage)
          @include('poems.components.translated', [
              'poem' => $translatedPoem,
              'currentPageId' => $currentPageId
          ])
        @endif
      @endforeach
    </div>
  @endif
</div>