@extends('layouts.common')

@push('styles')
  <link href="{{ mix('/css/search.css') }}" rel="stylesheet">
@endpush
@section('content')
<div class="error-page page">
    <div class="message">
        @lang('Not Found')
    </div>
</div>
@endsection
