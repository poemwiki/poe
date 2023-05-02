<?php
$createPageUrl = route('poems/create', ['original_fake_id' => $poem->topOriginalPoem->fake_id], false);
?>
@if($poem->is_translated && !$poem->originalPoem)
  <dt>@lang('poem.no original work related')</dt>
  <dd><a class="text-sm btn" href="{{ Auth::check() ? route('poems/create', ['translated_fake_id' => $poem->fake_id]) : route('login', ['ref' => route('poems/create', ['translated_fake_id' => $poem->fake_id], false)]) }}">
      @lang('poem.add original work')</a></dd><br>
@else
  <dt><a class="text-xs btn create add-translate-btn"
         href="{{ Auth::check() ? $createPageUrl : route('login', ['ref' => $createPageUrl]) }}">
      @lang('poem.add another translated version')
    </a>
  </dt>
@endif