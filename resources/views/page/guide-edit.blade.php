@extends(App\User::isWechat() ? 'layouts.webview' : 'layouts.fe')

@section('title')诗歌维基编辑公约@endsection
@section('author')
  PoemWiki
@endsection

@section('content')
  <h1>诗歌维基编辑公约</h1>
  <p>诗歌维基是一个人人可参与编辑的诗歌库。为保证诗歌、诗人条目的高质量，参与者应遵守以下约定：</p>

  <h2>格式和结构化</h2>
  <dl>
    <dt>一个诗歌条目中只包含一首诗</dt>
    <dt>认真确认诗词标题、作者、译者、来源、副标题、题记等信息，保留原文排版</dt>

    <dt>不在诗题处添加多余的符号（书名号、空格等）
      <div>
        <figure>
          <img src="/images/guide/title.jpg" />
          <figcaption><span class="error">❌ 错误示例</span>：诗题处添加多余的书名号和空格</figcaption>
        </figure>
        <figure>
          <img src="/images/guide/title-r.jpg" />
          <figcaption>✅ 正确示范</figcaption>
        </figure>
      </div>
    </dt>

    <dt>不在诗歌正文处添加多余空行和空格
      <div>
        <figure>
          <img src="/images/guide/empty-line.jpg" style="zoom: .7;" />
          <figcaption><span class="error">❌ 错误示例</span>：诗歌正文处添加多余空行</figcaption>
        </figure>
        <figure>
          <img src="/images/guide/empty-line-r.jpg" style="zoom: .7;" />
          <figcaption>✅ 正确示范：只保留原文空行和空格</figcaption>
        </figure>
      </div>
    </dt>

    <dt>根据上传时表单的提示，在标题、作者等字段填入对应的内容，不在诗歌正文部分添加多余的标题、作者、写作时间、写作地点等信息
      <div>
        <figure>
          <img src="/images/guide/fields-1.jpg" style="zoom: .9;" />
          <figcaption><span class="error">❌ 错误示例</span>：诗歌正文处不应添加作者名和标题</figcaption>
        </figure>
        <figure>
          <img src="/images/guide/fields.jpg" style="zoom: .9;" />
          <figcaption><span class="error">❌ 错误示例</span>：原诗并无题记，题记处不应添加作者信息和写作时间；诗歌正文部分不应添加赏析等和原文无关的内容</figcaption>
        </figure>
        <figure>
          <img src="/images/guide/fields-r.jpg" style="zoom: .7;" />
          <figcaption>✅ 正确示范：作者，写作时间填在相应的位置，诗歌正文部分保持和原文一致</figcaption>
        </figure>
      </div>
    </dt>
    <dt>不混用中英文标点</dt>
  </dl>

  <h2>原创和引用</h2>
  <dl>
    <dt>正确声明原创</dt>
    <dt>不抄袭别人作品</dt>
    <dt>引用、化用他人词句应在注释中指出</dt>
  </dl>

  <h2>不做任何有损内容质量的事情</h2>
  <dl>
    <dt>不上传诗歌无关内容（广告、灌水等）</dt>
    <dt>不上传低质量条目（整篇文章、名人语录、未确认来源的文字片段等）</dt>
    <dt>不恶意编辑</dt>
  </dl>
@endsection

@push('styles')
  <style>
    main{
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      justify-content: flex-start;
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