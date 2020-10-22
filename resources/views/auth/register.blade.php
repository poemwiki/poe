<!DOCTYPE html>
<html lang="en">
<head>
    <base href="./">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    @include('layouts.icon')
    @include('layouts.analyze')
    <title>{{config('app.name')}}</title>
    <meta name="author" content="PoemWiki">
    <meta name="description" content="PoemWiki">
    <meta name="keyword" content="poem,poetry,poet,诗，诗歌，诗人">

    <link rel="stylesheet" href="/css/vendor/coreui.min.css">
    <link rel="stylesheet" href="/css/vendor/simple-line-icons.min.css">

</head>
<body class="app flex-row align-items-center">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card mx-4">
                <div class="card-body p-4">
                    <form method="post" action="{{ url('/register/?invite_code_from='.app('request')->input('invite_code_from')) }}">
                        @csrf
                        <h1>@lang('Register')</h1>
                        <p class="text-muted">{{ config('invite_limited') ? App\User::inviteFromStr(app('request')->input('invite_code_from')) : '' }} 邀请您注册 <b><a target="_blank" href="/">PoemWiki</a></b></p>
                        <p class="text-muted">@lang('Create your account')</p>
                        @if (count($errors))
                            <p class="font-weight-bold text-danger">提交失败</p>
                        @endif
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                              <span class="input-group-text">
                                <i class="icon-user"></i>
                              </span>
                            </div>
                            <input type="text" class="form-control {{ $errors->has('name')?'is-invalid':'' }}" name="name" value="{{ old('name') }}"
                                   placeholder="@lang('Full Name')">
                            @if ($errors->has('name'))
                                <span class="invalid-feedback">
                                    <strong>{{ $errors->first('name') }}</strong>
                                </span>
                            @endif
                        </div>
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text">@</span>
                            </div>
                            <input type="email" class="form-control {{ $errors->has('email')?'is-invalid':'' }}" name="email" value="{{ old('email') }}" placeholder="@lang('Email Address')">
                            @if ($errors->has('email'))
                                <span class="invalid-feedback">
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
                            <input type="password" class="form-control {{ $errors->has('password')?'is-invalid':''}}" name="password" placeholder="@lang('Password')">
                            @if ($errors->has('password'))
                                <span class="invalid-feedback">
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
                                   placeholder="@lang('Confirm password')">
                            @if ($errors->has('password_confirmation'))
                                <span class="help-block">
                                  <strong>{{ $errors->first('password_confirmation') }}</strong>
                               </span>
                            @endif
                        </div>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    C
                                </span>
                            </div>
                            <input type="text" class="form-control {{ $errors->has('captcha')?'is-invalid':'' }}" name="captcha"
                                   placeholder="@lang('Input captcha code here')">
                            <img src="{{captcha_src()}}" onclick="this.src='{{captcha_src()}}'+Math.random()" alt="验证码">
                            @if ($errors->has('captcha'))
                                <span class="invalid-feedback">
                                    <strong>{{ $errors->first('captcha') }}</strong>
                                </span>
                            @endif
                        </div>


                        <button type="submit" class="btn btn-primary btn-block btn-flat mb-4">@lang('Register')</button>
                        <a href="{{ url('/login') }}" class="text-center">@lang('register.I already have a membership')</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
