<script src="{{ asset('/js/lib/color-hash.js') }}"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const colorHash = new ColorHash({lightness: 0.6, saturation: 0.86});

    const $next = document.querySelector('.next .title');
    const $body = document.getElementsByTagName("body")[0];
    const $head = document.head || document.getElementsByTagName('head')[0];

    const mainColor = colorHash.hex(document.querySelector('article .title').innerText);
    const nextMainColorRule = $next ? `--main-color-next: ${colorHash.hex($next.innerText)};` : '';

    // insert style to header
    const style = document.createElement('style');
    style.type = 'text/css';
    const cssText = `
      .poem-line::selection {
        color: ${mainColor};
      }
      :root {
        --main-color: ${mainColor};
        ${nextMainColorRule}
      }
    `;
    style.appendChild(document.createTextNode(cssText));
    $head.appendChild(style);

    const $nav = document.getElementById('top-nav');
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

      const selection = window.getSelection();

      //if the selection is short let's not annoy our users
      if (("" + selection).length < 10) return;

      //create a div outside the visible area
      const newdiv = document.createElement('div');
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