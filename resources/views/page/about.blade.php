@php
$isWeappWebview = App\User::isWeAppWebview();
@endphp
@extends($isWeappWebview ? 'layouts.webview' : 'layouts.fe')

@section('title')关于诗歌维基@endsection
@section('author')
  PoemWiki
@endsection

@section('content')
  <p>“<b>诗歌维基</b>”计划建立一个自带评价体系，且跨语种的诗歌库，收集并记录世界上的诗作，组成一个共有、自治的诗歌社区。</p>
  <p>该项目基于“读首诗再睡觉”发展而来，二者均致力于推荐优秀诗作、译作。</p>
  <p>项目将使用区块链记录所有人（包括读首诗再睡觉的重要参与者）的贡献，并在之后的过程中予以体现，并落实为每个人的投票权。为诗歌维基撰写、编辑诗作或诗人条目，将被计入贡献中。</p>
  <p>目前项目由几位发起人组成的“诗歌维基实验室”推动，我们在编辑、开发、设计、运营各环节都需要支持，如果有兴趣加入实验室，欢迎联系下方微信。</p>
  <p><img class="qr" src="{{cosUrl('/img/common/xfg-ds.jpg')}}" alt="二维码"></p>
  <img class="weapp" src="{{cosUrl('/img/common/wechat-search.png')}}" alt="微信扫码，使用诗歌维基小程序">

  @if(!$isWeappWebview)<p style="margin-top: 3em;">友情链接: &emsp;<a href="https://bedtimepoem.com" target="_blank">读首诗再睡觉</a>&emsp;<a href="https://www.chinese-poetry.org/" target="_blank">华语现代诗歌语料库</a></p>@endif

  <hr style="margin:3em 0 2em; width:100%; max-width:900px;">
  <h2 id="changelog" class="text-xl">更新日志</h2>
  <div class="changelog" style="width:100%; max-width:900px;">
    <h3>2025 年</h3>
    <ul>
      <li>更新：诗歌分享图片显示更完整的信息，包含副标题、题记、译者、日期、地点。</li>
      <li>修复推荐诗歌过于集中，容易重复的问题。</li>
      <li>用户页新增“我的五星”诗单：可查看评过五星的诗歌。</li>
      <li>更新 PoemWiki Open API 及其文档。</li>
      <li>体验：诗歌页与作者页加载速度提升。</li>
      <li>界面：图标与站点标识统一更新。</li>
      <li>账号与登录：恢复微信内浏览器内的微信登录功能。</li>
      <li>在作者页添加了排序按钮，可切换最热/最新两种排序方式。</li>
      <li>可用性：错误提示页、加载中提示组件、滚动与导航细节打磨。</li>
      <li>在“关于诗歌维基”页面添加了更新日志。</li>
    </ul>
    <h3>2023-2024 年</h3>
    <ul>
      <li>搜索与浏览：搜索结果样式与布局调整，支持更精确的匹配与展示。</li>
      <li>赛诗会活动：增加配置能力（如更严格的行数/格式控制）。</li>
      <li>新增不同版本译作/原作对比页面。</li>
      <li>评分与内容：显示更清晰的综合评分（满足最少评分数后展现）。</li>
      <li>提案与协作：引入提案链接，方便社区跟进规划。</li>
    </ul>
    <h3>2022 年</h3>
    <ul>
      <li>用户贡献：显示并记录社区贡献。</li>
      <li>作者与作品信息：补充更多数据字段（生卒年、国家/朝代等）。</li>
    </ul>
    <h3>2021 年</h3>
    <ul>
      <li>4 月上线诗歌维基微信小程序。</li>
      <li>搜索与发现：可按作者关联检索诗歌。</li>
      <li>内容质量：新增原创/翻译标识。</li>
    </ul>
    <h3>2020 年</h3>
    <ul>
      <li>7 月 21 日上线初始版本：作品与作者基础检索、上传与编辑、账号体系、头像与验证标识。</li>
    </ul>
  </div>
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
  .changelog h3{margin-top:2.2em;font-size:1.25em;}
  .changelog ul{list-style:disc;padding-left:1.2em;}
  .changelog li{margin:0.4em 0;}
  .changelog code{background:#f5f5f5;padding:0 4px;border-radius:3px;}
  </style>
@endpush