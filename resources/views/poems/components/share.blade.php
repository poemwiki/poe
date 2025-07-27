<div id="share-modal" class="fixed hidden w-screen h-screen items-center justify-center flex-col z-50">
  <div class="loading-box mb-4"></div>
  <p class="text-white">正在生成诗歌卡片</p>
</div>

@push('scripts')
  <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/1.3.6/axios.min.js" integrity="sha512-06NZg89vaTNvnFgFTqi/dJKFadQ6FIglD6Yg1HHWAUtVFFoXli9BZL4q4EO1UTKpOfCfW5ws2Z6gw49Swsilsg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <script>
    // event delegation
    function delegateEvent(element, eventType, selector, fn) {
      element.addEventListener(eventType, e => {
        let el = e.target;
        while(!el.matches(selector)) {
          if(element === el) {
            el = null;
            break;
          }
          el = el.parentElement;
        }
        el && fn.call(el, e, el);
      });
      return element;
    }

    delegateEvent(document.body, 'click', '.generate-share-img', onShare);

    const $modal = document.getElementById('share-modal');

    async function onShare (e, el) {
      const $shareBtn = el;
      const id = $shareBtn.dataset.id;
      const title = $shareBtn.dataset.title;
      const poet = $shareBtn.dataset.poet;

      const shareBtnHtml = $shareBtn.innerHTML;
      const url = `/api/v1/poem/share/${id}/pure`;

      function showModal() {
        $modal.classList.remove('hidden');
        $modal.classList.add('flex');
        $shareBtn.innerText = '正在生成...';
        $shareBtn.disabled = true;
      }

      function hideModal() {
        $modal.classList.remove('flex');
        $modal.classList.add('hidden');
        $shareBtn.innerHTML = shareBtnHtml;
        $shareBtn.disabled = false;
      }

      showModal();
      try {
        const res = await axios.get(url);

        hideModal();
        if(res.data.code !== 0) {
          alert('生成图片失败，请稍后再试');
          return;
        }

        const imgUrl = res.data.data.url;
        // download
        const a = document.createElement('a');
        a.href = imgUrl;
        a.download = `${title} - ${poet}`;
        a.click();
      } catch (e) {
        alert('生成图片失败，请稍后再试');
        hideModal();
      }
    }
  </script>
@endpush