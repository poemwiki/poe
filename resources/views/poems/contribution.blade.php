<?php
use Jfcherng\Diff\Differ;
use Jfcherng\Diff\DiffHelper;
use Jfcherng\Diff\Factory\RendererFactory;
?>@extends('layouts.fe')


@section('title'){{$poem->title}}@endsection
@section('author')
    PoemWiki
@endsection
@section('content')
<h2>{{($poem->poetLabel)}}&nbsp;&nbsp;<a href="{{$poem->url}}">{{$poem->title}}</a>&nbsp;&nbsp;@lang('poem.edit history')</h2>
<ol class="contribution">

    @foreach($poem->activityLogs as $key=>$log)
    <li class="log-group {{($key!==0 && $key!==count($poem->activityLogs)-1) ? 'log-middle' : ''}}">
        @php
            $newVal = $log->properties->get('attributes');
            $oldVal = $log->properties->get('old');
            $props = array_keys($newVal ?? []);

            //dd($poem->activityLogs);
            //dd($log);
        @endphp
        <span title="{{$log->created_at}} UTC">{{date_ago($log->created_at)}}</span>&nbsp;&nbsp;&nbsp;
        <b>{{get_causer_name($log)}}</b>&nbsp;&nbsp;
        <span>{{trans('poem.change type '.$log->description)}}</span>


        @if($log->description === 'updated')
            @foreach($props as $prop)
                @if(in_array($prop, \App\Models\Poem::$ignoreChangedAttributes))
                    @continue
                @endif
                @php
                    $old = $oldVal[$prop] ?? '';
                    $new = $newVal[$prop] ?? '';
                @endphp

            <div class="log">
                @if($prop === 'poem')
                    <span class="field">{{trans('admin.poem.columns.'.$prop)}}</span>
                  @php
                      $differOptions = [
                          // show how many neighbor lines
                          // Differ::CONTEXT_ALL can be used to show the whole file
                          'context' => 1,
                          // ignore case difference
                          'ignoreCase' => false,
                          // ignore whitespace difference
                          'ignoreWhitespace' => false,
                      ];
                      $rendererOptions = [
                          'detailLevel' => 'char', // (none, line, word, char)
                          'language' => 'chs',
                          'separateBlock' => true, // show a separator between different diff hunks in HTML renderers
                          'spacesToNbsp' => true
                      ];

                      $differ = new Differ(explode("\n", $old), explode("\n", $new), $differOptions);
                      $renderer = RendererFactory::make('SideBySide', $rendererOptions); // or your own renderer object
                      //dd($renderer, $old, $new);
                      $result = $renderer->render($differ);

                      echo $result;
                  @endphp
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
                    <span class="field">{{trans('admin.poem.columns.translator')}}</span>&nbsp;[&nbsp;
                    <del>@foreach($oldTrans as $k => $t)
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
                           @if($k < count($oldTrans)-1),&nbsp;@endif
                      @endforeach</del>&nbsp;⟹&nbsp;
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
                    &nbsp;]
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
            @endforeach
        @elseif($log->description === 'created')
            @lang('poem.initial version')
        @endif
    </li>

    @endforeach

    <!-- for poems imported from bedtimepoem, they have no "created" log -->
    @if(count($poem->activityLogs)<1 or $poem->activityLogs->last()->description !== 'created')
        <li title="{{$poem->created_at}}" class="log-group">
          <span title="{{$poem->created_at}} UTC">{{date_ago('2020-07-21')}}</span>&nbsp;&nbsp;&nbsp;
          <b>PoemWiki</b>&nbsp;&nbsp;
          <span>{{trans('poem.change type created')}}</span>@lang('poem.initial version')
        </li>
    @endif
</ol>
@endsection

@push('styles')
  <style>
    main {
      padding: 1em;
    }
    .field{
      display: inline-block;
      min-width: 6em;
      color: #9d9d9d;
    }

    .log-group{
      padding: 1em 0;
      line-height: 2em;
    }
    .log-group+.log-group{
      border-top: 1px solid #eee;
    }

    .diff{
      margin: -.5em 0 1em;
    }
    .diff th{
      font-weight: normal;
      font-size: 14px;
      color: #9d9d9d;
    }
    .change .old, .chang .new {
      vertical-align: baseline;
    }
    .change .n-new, .change .n-old{
      font: 14px/1.8 "Courier New", Courier, monospace;
      color: #9d9d9d;
      display: inline-block;
      margin-right: .5em;
    }
    .log del {
      background-color: lightpink;
      text-decoration: none;
    }
    .change .new>ins {
      background-color: rgb(190, 230, 190);
      text-decoration: none;
    }

  </style>
@endpush
