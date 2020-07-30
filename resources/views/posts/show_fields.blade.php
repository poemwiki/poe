<?php

$nation = $poem->dynasty
    ? "[$poem->dynasty] "
    : ($poem->nation ? "[$poem->nation] " : '');

$writer = $poem->poet_cn
    ? '作者 / '. $nation . $poem->poet_cn
    : ($poem->poet ? $poem->poet : '');

$parts = [
    $poem->poem."\n",
    $writer
];
if($poem->year) array_push($parts, $poem->year);
if($poem->translator) array_push($parts, '翻译 / '.trim($poem->translator));

$fullContent = implode("\n", $parts);
?>
<p class="title font-song">{{ $poem->title }}</p><br/>
<pre class="font-song">{{ $fullContent }}</pre>
<br>
<br>
@if($poem->bedtime_post_id)
<!-- Bedtime Post Id Field -->
<div class="form-group">
    <h4>荐诗</h4>
    <hr>
    @if($poem->bedtime_post_title)读首诗再睡觉博客：<a target="_blank" href="https://bedtimepoem.com/archives/{{ $poem->bedtime_post_id }}">{{ $poem->bedtime_post_title }}</a>
    @else<a target="_blank" href="https://bedtimepoem.com/archives/{{ $poem->bedtime_post_id }}">读睡博客荐诗</a>
    @endif
</div>
@endif
