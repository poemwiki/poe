@php
$isWeappWebview = App\User::isWeAppWebview();
@endphp
@extends($isWeappWebview ? 'layouts.webview' : 'layouts.fe')

@section('title')用户授权协议@endsection
@section('author')
  PoemWiki
@endsection

@section('content')
  <p>“<b>诗歌维基</b>”NFT 用户授权协议</p>
  <p>该项目基于“读首诗再睡觉”发展而来，二者均致力于推荐优秀诗作、译作。</p>
  <p>项目将使用区块链记录所有人（包括读首诗再睡觉的重要参与者）的贡献，并在之后的过程中予以体现，并落实为每个人的投票权。为诗歌维基撰写、编辑诗作或诗人条目，将被计入贡献中。</p>
  <p>目前项目由几位发起人组成的“诗歌维基实验室”推动，我们在编辑、开发、设计、运营各环节都需要支持，如果有兴趣加入实验室，欢迎联系下方微信。</p>
  <p><img class="qr" src="{{asset('images/xfg-ds.jpg')}}" alt="二维码"></p>
  <img class="weapp" src="{{asset('images/weapp.png')}}" alt="微信扫码，使用诗歌维基小程序">

@endsection

@push('styles')
  <style>
    main{
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: flex-start;
      padding: 1em;
      text-align: justify;
      line-height: 2em;
    }
    main p {
      width: 100%;
    }
    main .qr, .weapp{
      display: block;
      width: 200px;
      max-width: 60%;
      margin: 0 auto;
    }
    .weapp{
      width: 400px;
      max-width: 80%;
    }
  </style>
@endpush