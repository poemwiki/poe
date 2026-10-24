@if($poems->hasMorePages())
  <div class="author-poem-loader mt-4" data-poem-loader>
    <a class="btn btn-wire" data-load-more href="{{$poems->nextPageUrl()}}#{{$tab}}">加载更多</a>
    <span class="ml-3 text-gray-400" data-load-status role="status" aria-live="polite"></span>
  </div>
@endif
