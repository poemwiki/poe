@foreach($poems as $poem)
  <li class="title-list-item" data-poem-id="{{$poem->id}}">
    <a class="title font-song no-bg" href="{{$poem->url}}">
      {{trim($poem->title) ?: '无题'}}
    </a>
    <a class="first-line no-bg" href="{{$poem->url}}">
      {!!Str::of($poem->firstLine)->surround('span', function ($i) {
        return 'style="transition-delay:'.($i*20).'ms"';
      })!!}
      @if($type === 'translation')
        <span class="text-gray-400 float-right item-poem-author {{!empty($poetLabelMap[$poem->id]['author_id']) ? 'poemwiki-link' : ''}}">
          {{$poetLabelMap[$poem->id]['name'] ?? $poem->poet}}
        </span>
      @elseif(!$poem->isTranslated)
        @include('poems.fields.date', ['poem' => $poem, 'class' => 'text-gray-400 float-right item-poem-author'])
      @elseif($poem->translatorLabel)
        <span class="text-gray-400 float-right item-poem-author">
          @lang('Translated by', ['translator' => $poem->translatorsStr])
        </span>
      @endif
    </a>
  </li>
@endforeach
