@extends(App\User::isWeAppWebview() ? 'layouts.webview' : 'layouts.webview')

@section('title')赛诗会奖励领取@endsection
@section('author')
  PoemWiki
@endsection

@section('content')
  @if(isset($error) && $error==='')
    <h1>{{$campaign->name_lang}} 赛诗会奖励领取</h1>
    <p class="error">{{$error}}</p>

    <div class="reward">
      @foreach($awards as $award)
        <label class="reward-label">恭喜获得 {{$award->name}}，<a href="{{route('campaign/reward/show', $award->id)}}">点击领取奖励</a></label>
      @endforeach
    </div>

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
      width: 100%;
    }
    .reward{
      font-size: 1.2em;
      margin: 0;
    }
    .reward-label{
      width: 100%;
      display: block;
    }
    .reward-code{
      width: 50%;
      border: none;
      background: transparent;
      line-height: 4vw;
      height: 4vw;
      outline: none;
      text-align: center;
    }
    h1 {
      font-size: 1.6em;
      width: 100%;
      text-align: center;
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