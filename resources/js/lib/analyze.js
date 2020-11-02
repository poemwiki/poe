window.addEventListener('load', function () {
  // using setTimeout here to avoid affecting onload
  setTimeout(function () {
    var tag = document.createElement("script");
    tag.type = 'text/javascript';
    tag.async = true;
    tag.src = "https://hm.baidu.com/hm.js?505d57c136152b99b70cff9b0d943c8a";
    var s = document.getElementsByTagName("script")[0];
    s.parentNode.insertBefore(tag, s);
  }, 0);
});


// hotjar
(function(h,o,t,j,a,r){
  h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};
  h._hjSettings={hjid:2072396,hjsv:6};
  a=o.getElementsByTagName('head')[0];
  r=o.createElement('script');r.async=1;
  r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;
  a.appendChild(r);
})(window,document,'https://static.hotjar.com/c/hotjar-','.js?sv=');