<?php

$nation = $poem->dynasty
    ? "[$poem->dynasty] "
    : ($poem->nation ? "[$poem->nation] " : '');

$writer = $poem->poet_cn
    ? '作者 / '. $nation . $poem->poet_cn
    : ($poem->poet ? $poem->poet : '');

$from = $poem->from
    ? '选自 / '. $poem->from
    : null;

$parts = [
    $poem->poem."\n",
    $writer
];
if($poem->year) array_push($parts, $poem->year);
if($poem->translator) array_push($parts, '翻译 / '.trim($poem->translator));

$fullContent = implode("\n", $parts);
?>

@if (Route::has('login'))
    <div class="top-right links no-select">
        <a class="site-name" href="{{$randomPoemUrl}}" style="white-space: pre-line">POEM&#0010;Wiki</a>
    </div>
@endif

<p class="title font-song no-select" id="title">{{ $poem->title }}
    @auth
        <a class="edit" href="{{ url('/posts/edit/' . $fakeId) }}" title="编辑"><svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" style="height: 1em;
    vertical-align: baseline;" xml:space="preserve">
<g><path d="M446.029,0L130.498,267.303l-20.33,66.646c-8.624,7.369-19.857,11.39-32.017,11.391c-4.776,0-9.583-0.622-14.293-1.848
			l-14.438-3.761L0,512l172.268-49.421l-3.759-14.438c-4.454-17.1-0.883-34.137,9.54-46.309l66.648-20.331L512,65.971L446.029,0z
			 M136.351,441.068l-61.413,17.618l42.732-42.732L96.045,394.33l-42.731,42.732l17.627-61.444c2.401,0.202,4.807,0.303,7.21,0.303
			c16.215-0.001,31.518-4.56,44.35-13.043l26.609,26.609C139.202,404.41,134.73,422.458,136.351,441.068z M173.977,371.102
			l-33.079-33.078l10.109-33.14l56.109,56.109L173.977,371.102z M235.003,345.632l-68.636-68.636l46.828-39.671l61.478,61.478
        L235.003,345.632z M236.61,217.492L444.314,41.535l26.152,26.152L294.509,275.391L236.61,217.492z"/></g>
</svg></a>
    @endauth</p>
<pre class="font-song no-select">{{ $fullContent }}</pre>
<br>
<br>
@if($poem->bedtime_post_id)
<!-- Bedtime Post Id Field -->
<div class="side">
    <h4 class="side-title">荐诗</h4>
    <hr>
    @if($poem->bedtime_post_title)读首诗再睡觉：<a target="_blank" href="https://bedtimepoem.com/archives/{{ $poem->bedtime_post_id }}">{{ $poem->bedtime_post_title }}</a>
    @else<a target="_blank" href="https://bedtimepoem.com/archives/{{ $poem->bedtime_post_id }}">读睡博客荐诗</a>
    @endif
</div>
@endif


<script src="{{ asset('js/color-hash.js') }}"></script>
<script>
    var colorHash = new ColorHash();
    var mainColor = colorHash.hex('{{ $poem->title }}'); // '#8796c5'
    document.getElementById("title").style.setProperty('--main-color', mainColor);
</script>
