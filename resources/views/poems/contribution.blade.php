@extends('layouts.fe')


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

                @include('poems.log.log')

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
    .log.poem del{
      white-space: pre;
    }
    .change .new>ins {
      background-color: rgb(190, 230, 190);
      text-decoration: none;
    }

  </style>
@endpush
