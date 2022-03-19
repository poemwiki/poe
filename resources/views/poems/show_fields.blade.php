<?php
/** @var Poem $poem */
$cover = $poem->wx->get(0) ? $poem->wx->get(0)->cover_src : 'https://poemwiki.org/icon/apple-touch-icon.png'
?>
@section('canonical')<link rel="canonical" href="{{$poem->url}}" />@endsection
{{--TODO 支持多语言版本UI，并且在 alternate section 列出诗歌对应语言版本的url 例如： <link rel="alternate" href="{{$poem->getAlternateUrl('en') ?: 'https://en.poemwiki.org/p/'.$poem->fake_id}}" hreflang="en" /> --}}
@section('alternate')<link rel="alternate" href="{{$poem->url}}" hreflang="x-default" />@endsection
@section('title'){{$poem->title}}@endsection
@section('author'){{$poem->poet.($poem->poet ? ',' : '').$poem->poet_cn}}@endsection
@section('meta-og')
    <meta property="og:title" content="{{$poem->title}}" />
    <meta property="og:url" content="{{$poem->url}}" />
    <meta property="og:image" content="{{$cover}}" />
    <meta property="og:description" content="{{$poem->firstLine}}" />
    <meta property="og:site_name" content="PoemWiki 诗歌维基" />
    <meta property="og:type" content="article" />
    <meta property="og:article:author" content="" />


    <meta property="twitter:card" content="summary" />
    <meta property="twitter:image" content="{{$cover}}" />
    <meta property="twitter:title" content="{{$poem->title}}" />
    <meta property="twitter:creator" content="{{$poem->poet}}" />
    <meta property="twitter:site" content="PoemWiki 诗歌维基" />
    <meta property="twitter:description" content="{{$poem->firstLine}}" />
@endsection


  @include('poems.components.poem', ['poem' => $poem])

    @livewire('score', [
    'poem' => $poem
    ])

    <nav class="next">
        <span>@lang('Next Poem')</span>
        <p>
            <a class="no-bg title font-hei no-select title-bar" href="{{$randomPoemUrl}}">{{$randomPoemTitle}}</a>
            <a class="first-line no-bg" href="{{$randomPoemUrl}}">{!!
                Str::of($randomPoemFirstLine)->surround('span', function ($i) {
                    return 'style="transition-delay:'.($i*20).'ms"';
                })!!}
            </a>
        </p>
    </nav>

@push('scripts')
<script src="{{ asset('/js/lib/color-hash.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var colorHash = new ColorHash({lightness: 0.6, saturation: 0.86});
    var mainColor = colorHash.hex(document.querySelector('article .title').innerText);
    var mainColorNext = colorHash.hex(document.querySelector('.next .title').innerText);
    var $body = document.getElementsByTagName("body")[0];
    $body.style.setProperty('--main-color', mainColor);
    $body.style.setProperty('--main-color-next', mainColorNext);

    var $nav = document.getElementById('top-nav');
    window.addEventListener('scroll', function(e) {
        if(window.scrollY >= 60) {
            $nav.classList.add('show-title');
        } else {
            $nav.classList.remove('show-title');
        }
    });
    $nav.addEventListener('dbclick', function () {
        window.scrollTo({top:0});
    });


  $body.addEventListener('copy', function (e) {
    if (typeof window.getSelection == "undefined") return; //IE8 or earlier...

    var selection = window.getSelection();

    //if the selection is short let's not annoy our users
    if (("" + selection).length < 10) return;

    //create a div outside of the visible area
    var newdiv = document.createElement('div');
    newdiv.style.position = 'absolute';
    newdiv.style.left = '-99999px';
    $body.appendChild(newdiv);
    newdiv.appendChild(selection.getRangeAt(0).cloneContents());

    //we need a <pre> tag workaround
    //otherwise the text inside "pre" loses all the line breaks!
    if (selection.getRangeAt(0).commonAncestorContainer.nodeName === "PRE") {
      newdiv.innerHTML = "<pre>" + newdiv.innerHTML + "</pre>";
    }

    newdiv.innerHTML += "<br /><br />PoemWiki&nbsp;<a href='"
      + '{!!$poem->weapp_url ? $poem->weapp_url['url'] : $poem->url!!}' + "'>"
      + '{!!$poem->weapp_url ? $poem->weapp_url['url'] : $poem->url!!}' + "</a>";

    selection.selectAllChildren(newdiv);
    window.setTimeout(function () { $body.removeChild(newdiv); }, 200);
  });
});

</script>
@endpush