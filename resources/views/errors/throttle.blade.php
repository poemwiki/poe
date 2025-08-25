@extends('layouts.common')

@push('styles')
  <link href="{{ mix('/css/base.css') }}" rel="stylesheet">
@endpush
@section('content')
<div class="error-page page">
    <div class="message">
        @lang('Too Many Requests')
    </div>
</div>
@endsection
