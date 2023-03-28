@php

$token = Cache::remember('lark-tenant-access-token', 110 * 60, function () {
  // get lark api token
  $res = Http::post('https://open.larksuite.com/open-apis/auth/v3/tenant_access_token/internal', [
      'app_id' => config('app.lark.app_id'),
      'app_secret' => config('app.lark.app_secret')
  ]);

  if($res['code'] !== 0){
      exit;
  }
  return $res['tenant_access_token'];
});

$doc = Cache::remember('lark-docx', 15 * 60, function () use ($token) {
  // get docx blocks form lark api
  $doc = Http::withHeaders([
      'Authorization' => 'Bearer '.$token
  ])->get('https://open.larksuite.com/open-apis/docx/v1/documents/JvqZduINZoPudcxqmtuu07uXsQg/blocks?document_revision_id=-1&page_size=500')->json();

  if($doc['code'] !== 0){
      exit;
  }

  return $doc;
});

$titleBlock = $doc['data']['items'][0];
$blocks = $doc['data']['items'];
@endphp

@extends(App\User::isWechat() ? 'layouts.webview' : 'layouts.fe')

@section('title')提案与积分@endsection
@section('author')
  PoemWiki
@endsection

@section('content')
  <h1>{{$titleBlock['page']['elements'][0]['text_run']['content']}}</h1>
  @foreach($blocks as $item)
    @if($item['block_type'] == 2)
      @foreach($item['text']['elements'] as $element)
        @if(isset($element['mention_doc']))
          <p><a href="{{$element['mention_doc']['url']}}" target="_blank">{{$element['mention_doc']['title']}}</p>
        @endif
      @endforeach
    @endif
  @endforeach

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
    h1 {
      width: 100%;
      text-align: center;
      font-size: 1.4em;
      line-height: 2em;
    }
  </style>
@endpush