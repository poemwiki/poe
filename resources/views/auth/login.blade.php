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
        <div class="col-md-10 col-lg-8 col-sm-12">
            <div class="card-group">
                <div class="card p-4">
                    <div class="card-body">
                        <form method="post" action="{{ route('login', ['ref' => request()->input('ref', '')]) }}">
                            @csrf
                            <h1>@lang('Login')</h1>
                            <p class="text-muted">{{config('app.name')}}</p>
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                      <i class="icon-user"></i>
                                    </span>
                                </div>
                                <input type="email" class="form-control {{ $errors->has('email')?'is-invalid':'' }}" name="email" value="{{ old('email') }}"
                                       placeholder="Email">
                                @if ($errors->has('email'))
                                    <span class="invalid-feedback">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="input-group mb-4">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                      <i class="icon-lock"></i>
                                    </span>
                                </div>
                                <input type="password" class="form-control {{ $errors->has('password')?'is-invalid':'' }}" placeholder="密码" name="password">
                                @if ($errors->has('password'))
                                    <span class="invalid-feedback">
                                       <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="input-group mb-4">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">C</span>
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

                            <div class="row">
                                <div class="col-6 text-left">
                                    <a class="btn btn-link px-0" href="{{ url('/password/reset') }}">
                                        @lang('Forget Password')
                                    </a>
                                </div>
                                <div class="col-6 text-right">
                                    <button class="btn btn-primary px-3" type="submit">登录</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card text-white bg-primary py-5">
                    <div class="card-body text-center">
                        <div>
                            <h2>@lang('Register')</h2>
                            <p>如果您还没有账号，<br>欢迎注册 PoemWiki！</p>
                            <a class="btn btn-primary active mt-3" href="{{ url('/register') }}">@lang('Register')</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
