@extends('layouts.form')

@php
  /** @var \App\Models\Author $author */
@endphp
@section('title', trans('auth.password.action.reset') )

@section('form')


  <div class="error-page page">
    <form method="post" action="{{ url('/password/reset') }}">
      @csrf
      <input type="hidden" name="token" value="{{ $token }}">
      <p class="text-muted text-lg">@lang('auth.password.Please set your new password')</p>
      <div class="input-group mb-3">
        <input type="email" class="form-control {{ $errors->has('email')?'is-invalid':'' }}" name="email"
               value="{{ $email }}" placeholder="Email">
        @if ($errors->has('email'))
          <span class="invalid-feedback ml-2">
              <strong>{{ $errors->first('email') }}</strong>
          </span>
        @endif
      </div>
      <div class="input-group mb-3">
        <div class="input-group-prepend">
                              <span class="input-group-text">
                                <i class="icon-lock"></i>
                              </span>
        </div>
        <input type="password" class="form-control {{ $errors->has('password')?'is-invalid':''}}" name="password"
               placeholder="Password">
        @if ($errors->has('password'))
          <span class="invalid-feedback ml-2">
              <strong>{{ $errors->first('password') }}</strong>
          </span>
        @endif
      </div>
      <div class="input-group mb-4">
        <div class="input-group-prepend">
            <span class="input-group-text">
              <i class="icon-lock"></i>
            </span>
        </div>
        <input type="password" name="password_confirmation" class="form-control"
               placeholder="Confirm password">
        @if ($errors->has('password_confirmation'))
          <span class="help-block">
              <strong>{{ $errors->first('password_confirmation') }}</strong>
           </span>
        @endif
      </div>
      <button type="submit" class="btn btn-wire btn-primary btn-block btn-flat">
        <i class="fa fa-btn fa-refresh"></i> @lang('auth.password.action.reset')
      </button>
    </form>
  </div>
@endsection

