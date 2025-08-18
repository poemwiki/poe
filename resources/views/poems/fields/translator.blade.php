@php
  // translators relation might not be hydrated due to cache timing; provide a lazy fallback using translatorsStr
  $translatorCollection = $poem->translators; // accessor will resolve and may use cached_translators
  $translatorCount = $translatorCollection->count();
@endphp

@if($translatorCount)
  <dt>@lang('admin.poem.columns.translator')</dt>
  <dd itemprop="translator" class="poem-translator">
    @foreach($translatorCollection as $translator)
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