<dt>@lang('admin.poem.columns.poet')</dt>
<dd itemscope itemtype="https://schema.org/Person">
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
</dd>