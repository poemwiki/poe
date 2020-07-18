@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8" style="margin-top: 2%">
                <div class="card" style="width: 40rem;">
                    <div class="card-body">
                        <h4 class="card-title">请打开您的邮箱，点击其中的激活链接</h4>
                        @if (session('resent'))
                            <p class="alert alert-success" role="alert">一封包含激活链接的邮件已发送至您的邮箱。</p>
                        @endif
                        <p class="card-text">如果还没有收到邮件，请点击下方按钮：</p>
                        <form method="POST" action="{{ route('verification.resend') }}">
                            @csrf
                            <button class="btn btn-primary" type="submit">@lang('verify.resend')</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
