<!-- poem {{$poem->id}} url and it's translated poems list -->
@php
/** @var \App\Models\Poem $poem */
@endphp

<div class="child">

  @php
    $children = $poem->translatedPoems;
    $childrenCount = $children->count();
    /** @var int $currentPageOriginalId */
    $isTranslatedFrom = $poem->id === $currentPageOriginalId;
  @endphp

  @if(($poem->id !== $currentPageId) or $childrenCount)
  <a href="{{$poem->url}}" @if($isTranslatedFrom) title="@lang('Translated from this version')" @endif>
    <dt>
      @if($isTranslatedFrom)
        <span class="translated-from">@lang('Translated from')</span>&nbsp;
      @endif
      {{$poem->lang->name_lang ?? trans('poem.other')}}
      {{$poem->is_original ? '['.trans('poem.original work').']' : ''}}
    </dt>

    <dd>
      @if($poem->is_original)
        {{$poem->poet_label}}
      @else
        {{$poem->translator_label ?: trans('No Name')}}
      @endif
      {{$poem->is_original ? $poem->title : ''}}
    </dd>
  </a>
  @endif

  @if($childrenCount)
    <div class="parent">
      @foreach($children as $translatedPoem)
        @if($translatedPoem->id !== $currentPageId)
          @include('poems.components.translated', [
                'poem' => $translatedPoem,
                'currentPageId' => $currentPageId
            ])
        @endif
      @endforeach
    </div>
  @endif
</div>