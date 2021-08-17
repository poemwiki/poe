@extends(App\User::isWechat() ? 'layouts.webview' : 'layouts.fe')

@section('title')赛诗会奖励领取@endsection
@section('author')
  PoemWiki
@endsection

@section('content')
  <h1>赛诗会奖励领取</h1>
  @if(isset($error))
    <p class="error">{{$error}}</p>
  @elseif($campaignId === 21 or $campaignId === 19)
    <p>{{$reward}}</p>
    <p>复制兑换码后，请下载或打开喜马拉雅App，登录账户后，点击“账号-VIP会员”，进入“我的VIP会员”页，找到“使用兑换码”进行兑换。请在领取后半年内兑换。</p>
  @endif
@endsection

@push('styles')
  <style>
    main{
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      justify-content: flex-start;
      height: 90vh;
      padding: 1em;
      text-align: justify;
      line-height: 2em;
    }
    main p {
      width: 100%;
    }
    h1 {
      width: 100%;
      text-align: center;
      font-size: 1.4em;
      line-height: 2em;
    }
    main h2{
      display: block;
      font-weight: bold;
      font-size: 1.2em;
    }
    dl{width: 100%;margin-bottom: 1em;}
    dl>dt {
      display: list-item;
      list-style: inside decimal;
      font-weight: normal;
      border: transparent 0;
      border-radius: .2em;
      padding: 0 .2em;
      text-align: left;
    }
    dl>dt:hover {
      background-color: #f2f2f2;
    }
    figure{
      margin: 0 0 1rem;
      padding: 1em;
      text-align: left;
      color: gray;
      display: inline-block;
      max-width: 100%;
      overflow-x: hidden;
    }
    figcaption {
      text-align: center;
    }
    .error {
      color: #c82f25;
    }
  </style>
@endpush