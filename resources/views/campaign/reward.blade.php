@extends(App\User::isWeAppWebview() ? 'layouts.webview' : 'layouts.fe')

@section('title')赛诗会奖励领取@endsection
@section('author')
  PoemWiki
@endsection

@section('content')
  @if(isset($error) && $error!=='')
    <h1>赛诗会奖励领取</h1>
    <p class="error">{{$error}}</p>
    <p class="error"><img class="code" src="{{asset('/images/campaign/dushuijun.jpg')}}" alt="读睡君"></p>
  @elseif($campaignId === 20)
    @if($results->count() === 1)
      <img class="full-vw" src="{{asset('/images/campaign/xmly.jpg')}}" alt="领取说明">
      <input class="reward" value="{{$results->first()->reward->reward}}" disabled />
    @endif
    @if($results->count() >= 2)
      <img class="full-vw" src="{{asset('/images/campaign/xmly3.jpg')}}" alt="领取说明">
      @foreach($results as $key => $result)
      <input class="reward reward-{{$key}}" value="{{$result->reward->reward}}" disabled />
      @endforeach
    @endif
{{--    <p>复制兑换码后，请下载或打开喜马拉雅App，登录账户后，点击“账号-VIP会员”，进入“我的VIP会员”页，找到“使用兑换码”进行兑换。请在领取后半年内兑换。</p>--}}
  @endif
@endsection

@push('head-scripts')
  <script>

  </script>
@endpush

@push('styles')
  <style>
    * {
      box-sizing: border-box;
    }
    main{
      display: flex;
      padding: 0;
      flex-direction: column;
      align-items: flex-start;
      justify-content: flex-start;
      text-align: justify;
      line-height: 2em;
      position: relative;
      width: 100%;
    }
    main p {
      width: 100%;
    }
    .full-vw{
      width: 100vw;
    }
    .reward{
      position: absolute;
      display: inline-block;
      width: 56vw;
      top: 99.5vw;
      left: 50vw;
      transform: translateX(-50%);
      font-size: 4vw;
      margin: 0;
      border: none;
      background: transparent;
      line-height: 12vw;
      height: 12vw;
      outline: none;
      text-align: center;
    }
    .reward-1 {
      top: 112.5vw;
    }
    .reward-2 {
      top: 124.5vw;
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