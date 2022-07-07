
<div class="log poem">
  @if($prop === 'poem')
    <span class="field">{{trans('admin.poem.columns.poem')}}</span>
    @include('poems.log.poem')

  @elseif($prop === 'poet_id')
    @php
      $oldAuthor = $old ? App\Models\Author::find($old) : null;
      $newAuthor = $new ? App\Models\Author::find($new) : null;
    @endphp
    <span class="field">{{trans('admin.poem.columns.poet_id')}}</span>&nbsp;[&nbsp;
    <del>@if($oldAuthor) <a href="{{$oldAuthor->url}}">{{$oldAuthor->label}}</a> @endif</del>&nbsp;⟹&nbsp;
    @if($newAuthor) <a href="{{$newAuthor->url}}">{{$newAuthor->label}}</a> @endif
    &nbsp;]

  @elseif($prop === 'translator_id')
    @php
      $oldTranslator = $old ? App\Models\Author::find($old) : null;
      $newTranslator = $new ? App\Models\Author::find($new) : null;
    @endphp
    <span class="field">{{trans('admin.poem.columns.translator_id')}}</span>&nbsp;[&nbsp;
    <del>@if($oldTranslator) <a href="{{$oldTranslator->url}}">{{$oldTranslator->label}}</a> @endif</del>&nbsp;⟹&nbsp;
    @if($newTranslator) <a href="{{$newTranslator->url}}">{{$newTranslator->label}}</a> @endif
    &nbsp;]

  @elseif($prop === 'translator')
    @php
      $oldTrans = json_decode($old);
      $oldTrans = is_array($oldTrans) ? $oldTrans : [$old];

      $newTrans = json_decode($new);
      $newTrans = is_array($newTrans) ? $newTrans : [$new];
    @endphp
    <span class="field">{{trans('admin.poem.columns.translator')}}</span>&nbsp;[
    @foreach($oldTrans as $k => $t)
      @if(is_string($t))
        <del><code>{{$t}}</code></del>
      @elseif(is_numeric($t))
        @php
          $transAuthor = \App\Models\Author::find($t);
        @endphp
        @if($transAuthor)
          <del><a href="{{$transAuthor->url}}">{{$transAuthor->label}}</a></del>
        @endif
      @endif
      @if($k < count($oldTrans)-1),&nbsp;@endif
    @endforeach⟹
    @foreach($newTrans as $i => $t)
      @if(is_string($t))
        {{$t}}
      @elseif(is_numeric($t))
        @php
          $transAuthor = \App\Models\Author::find($t);
        @endphp
        @if($transAuthor)
          <a href="{{$transAuthor->url}}">{{$transAuthor->label}}</a>
        @endif
      @endif
      @if($i < count($newTrans)-1),&nbsp;@endif
    @endforeach
    ]

  @elseif($prop === 'content_id')
    @php
      // TODO Why poem.content_id not stored into log?
      $oldContent = $old ? App\Models\Content::find($old) : null;
      $newContent = $new ? App\Models\Content::find($new) : null;
      // <span class="field">{{trans('admin.poem.columns.content_id')}}</span>&nbsp;[&nbsp;<del>{{$oldContent ? $oldContent->content : ''}}</del>&nbsp;⟹&nbsp;{{$newContent ? $newContent->content : ''}}&nbsp;]
    @endphp
    <span class="field">{{trans('admin.poem.columns.content_id')}}</span>&nbsp;[&nbsp;<del>{{$old}}</del>&nbsp;⟹&nbsp;{{$new}}&nbsp;]

  @elseif($prop === 'is_original')
    <span class="field">{{trans('admin.poem.columns.is_original')}}</span>&nbsp;[&nbsp;<del>{{trans_choice('admin.poem.is_original', $old)}}</del>&nbsp;⟹&nbsp;{{trans_choice('admin.poem.is_original', $new)}}&nbsp;]

  @elseif($prop === 'language_id')
    <span class="field">{{trans('admin.poem.columns.language_id')}}</span>&nbsp;[&nbsp;<del>{{$old ? $languageList[$old]->name_lang : ''}}</del>&nbsp;⟹&nbsp;{{$new ? $languageList[$new]->name_lang : ''}}&nbsp;]

  @elseif($prop === 'genre_id')
    <span class="field">{{trans('admin.poem.columns.genre_id')}}</span>&nbsp;[&nbsp;<del>{{$old ? $genreList[$old]->name_lang : ''}}</del>&nbsp;⟹&nbsp;{{$new ? $genreList[$new]->name_lang : ''}}&nbsp;]

  @elseif($prop === 'original_id')
    @php
      $oldPoem = $old ? App\Models\Poem::find($old) : null;
      $newPoem = $new ? App\Models\Poem::find($new) : null;
      $oldLink = $oldPoem ? "<a href=\"$oldPoem->url\">$oldPoem->title</a>" : '';
      $newLink = $newPoem ? "<a href=\"$newPoem->url\">$newPoem->title</a>" : '';
    @endphp
    <span class="field">{{trans('admin.poem.columns.original_id')}}</span>&nbsp;[&nbsp;<del>{!!$oldLink!!}</del>&nbsp;⟹&nbsp;{!!$newLink!!}&nbsp;]

  @else
    <span class="field">{{trans('admin.poem.columns.'.$prop)}}</span>&nbsp;[&nbsp;<del>{{$old}}</del>&nbsp;⟹&nbsp;{{$new}}&nbsp;]
  @endif
</div>