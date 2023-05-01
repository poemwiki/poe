@php
$token = Cache::remember('lark-tenant-access-token', 110 * 60, function () {
  // get lark api access token
  $res = Http::post('https://open.larksuite.com/open-apis/auth/v3/tenant_access_token/internal', [
      'app_id' => config('app.lark.app_id'),
      'app_secret' => config('app.lark.app_secret')
  ]);

  if($res['code'] !== 0){
      exit;
  }
  return $res['tenant_access_token'];
});

$http = Http::withHeaders([
    'Authorization' => 'Bearer '.$token
]);
$docTTL = 5 * 60;
$docID = 'JvqZduINZoPudcxqmtuu07uXsQg';
$doc = Cache::remember('lark-doc', $docTTL, function () use ($http, $token, $docTTL, $docID) {
  // get docx blocks form lark api
  $url = 'https://open.larksuite.com/open-apis/docx/v1/documents/'.$docID.'/blocks?document_revision_id=-1&page_size=500';
  return $http->get($url)->json();
});

if($doc['code'] !== 0){
    exit;
}

foreach ($doc['data']['items'] as $key => &$item) {
    if($item['block_type'] == 2 && isset($item['text']['elements'][0]['mention_doc'])){
        $docToken = $item['text']['elements'][0]['mention_doc']['token'];
        $proposalUrl = 'https://open.larksuite.com/open-apis/docx/v1/documents/' . $docToken;
        $proposal = Cache::remember('lark-doc-' . $docToken, $docTTL * ($key + 1), function() use ($http, $proposalUrl) {
            return $http->get($proposalUrl)->json();
        });

        if($proposal['code'] === 0){
            $item['text']['elements'][0]['mention_doc']['real_title'] = $proposal['data']['document']['title'];
        }
    }
}

$titleBlock = $doc['data']['items'][0];
$blocks = $doc['data']['items'];
@endphp

@extends(App\User::isWeAppWebview() ? 'layouts.webview' : 'layouts.fe')

@section('title')提案与积分@endsection
@section('author')
  PoemWiki
@endsection

@section('content')
  <h1 class="text-2xl mb-8">{{$titleBlock['page']['elements'][0]['text_run']['content']}}</h1>
  @foreach($blocks as $block)
    @if($block['block_type'] == 2)
      @foreach($block['text']['elements'] as $element)
        @if(isset($element['mention_doc']))
          <p class="mb-4"><a href="{{$element['mention_doc']['url']}}" target="_blank">{{$element['mention_doc']['real_title'] ?? $element['mention_doc']['title']}}</p>
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