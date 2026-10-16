<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (!window.fetch || !window.IntersectionObserver) return;

    document.querySelectorAll('[data-poem-loader]').forEach(function (loader) {
      var link = loader.querySelector('[data-load-more]');
      var status = loader.querySelector('[data-load-status]');
      var list = loader.parentElement.querySelector('.poems-list');
      var loading = false;
      var failed = false;
      var observer = new IntersectionObserver(function (entries) {
        if (entries.some(function (entry) { return entry.isIntersecting; }) && !failed) load();
      }, {rootMargin: '200px'});

      function load() {
        if (loading || !link.href) return;
        loading = true;
        failed = false;
        loader.setAttribute('aria-busy', 'true');
        status.textContent = '正在加载…';

        fetch(link.href.split('#')[0], {headers: {'Accept': 'application/json'}, credentials: 'same-origin'})
          .then(function (response) {
            if (!response.ok) throw new Error('Unable to load poems');
            return response.json();
          })
          .then(function (data) {
            list.insertAdjacentHTML('beforeend', data.html);
            status.textContent = '';
            if (data.next_url) {
              var hash = link.hash;
              link.href = data.next_url + hash;
            } else {
              observer.disconnect();
              link.remove();
              status.textContent = '已加载全部';
            }
          })
          .catch(function () {
            failed = true;
            status.textContent = '加载失败，请点击重试';
          })
          .finally(function () {
            loading = false;
            loader.removeAttribute('aria-busy');
            if (!failed && link.isConnected) {
              observer.unobserve(loader);
              observer.observe(loader);
            }
          });
      }

      link.addEventListener('click', function (event) {
        event.preventDefault();
        load();
      });
      observer.observe(loader);
    });
  });
</script>
