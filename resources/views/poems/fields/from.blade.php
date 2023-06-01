@if($poem->from)
  <dt>@lang('admin.poem.columns.from')</dt>
  <dd itemprop="isPartOf" class="poem-from">
    <p>{!!renderLink($poem->from)!!}</p>
  </dd><br>
@endif