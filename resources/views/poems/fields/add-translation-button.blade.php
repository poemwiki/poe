<?php
// Safely resolve top original poem; fall back to current poem when null
/** @var \App\Models\Poem $poem */
$topOriginal = $poem->topOriginalPoem ?: $poem;
$createPageUrl = route('poems/create', ['original_fake_id' => $topOriginal->fake_id], false);
?>
@if($poem->is_translated && !$poem->originalPoem)
  <dt>@lang('poem.no original work related')</dt>
  <dd><a class="text-sm btn" href="{{ urlOrLoginRef(route('poems/create', ['translated_fake_id' => $poem->fake_id], false)) }}">
      @lang('poem.add original work')</a></dd><br>
@else
  <dt><a class="text-xs btn create add-translate-btn"
         href="{{ urlOrLoginRef($createPageUrl) }}">
      @lang('poem.add another translated version')
    </a>
  </dt>
@endif
