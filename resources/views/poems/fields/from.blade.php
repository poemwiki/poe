@if($poem->from)
  <dt>@lang('admin.poem.columns.from')</dt>
  <dd itemprop="isPartOf" class="poem-from">@if(isValidUrl($poem->from))
      <a href="{{$poem->from}}" target="_blank">{{$poem->from}}<a>
    @else
      {{$poem->from}}
    @endif
  </dd><br>
@endif