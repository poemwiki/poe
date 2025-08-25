@extends('layouts.common')


@push('styles')
  <link href="{{ mix('/css/base.css') }}" rel="stylesheet">
@endpush
@section('content')
<div class="error-page page">
    <div class="message">
        @lang('Not Found')
        <div class="redirect-tip text-sm text-gray-500 mt-4">
            页面不存在，<span id="countdown">3</span> 秒后将返回首页。如未自动跳转，<a class="text-blue-600 underline" href="{{ url('/') }}">点击这里</a>。
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
  // Auto redirect to homepage after 3 seconds with visible countdown
  document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('countdown');
    var seconds = el ? parseInt(el.textContent, 10) : 3;
    var interval = setInterval(function () {
      seconds--;
      if (el) el.textContent = seconds;
      if (seconds <= 0) {
        clearInterval(interval);
        window.location.href = '{{ url('/') }}';
      }
    }, 1000);
  });
</script>
@endpush
