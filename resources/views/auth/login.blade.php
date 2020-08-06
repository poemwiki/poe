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

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@coreui/coreui@2.1.16/dist/css/coreui.min.css" integrity="sha256-lvsgjUQT72IM3r+HhRaDv7v2h6LS1707C13IAid9vwo=" crossorigin="anonymous">
    <link rel="stylesheet" href="/css/simple-line-icons.min.css">

</head>
<body class="app flex-row align-items-center">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card-group">
                <div class="card p-4">
                    <div class="card-body">
                        <form method="post" action="{{ url('/login') }}">
                            @csrf
                            <h1>登录</h1>
                            <p class="text-muted">登录到 PoemWiki</p>
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
                            <div class="row">
                                <div class="col-6">
                                    <button class="btn btn-primary px-4" type="submit">登录</button>
                                </div>
                                <div class="col-6 text-right">
                                    <a class="btn btn-link px-0" href="{{ url('/password/reset') }}">
                                        忘记密码
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card text-white bg-primary py-5 d-md-down-none">
                    <div class="card-body text-center">
                        <div>
                            <h2>注册</h2>
                            <p>如果您还没有账号，<br>欢迎注册 PoemWiki！</p>
                            <a class="btn btn-primary active mt-3" href="{{ url('/register') }}">注册</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
