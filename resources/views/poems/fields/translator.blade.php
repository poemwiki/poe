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