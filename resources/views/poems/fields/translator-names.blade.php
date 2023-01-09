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