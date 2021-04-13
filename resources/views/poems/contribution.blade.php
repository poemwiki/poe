@extends('layouts.fe')


@section('title'){{$poem->title}}@endsection
@section('author')
    PoemWiki
@endsection
@section('content')
<h2>{{($poem->poet_cn ?? $poem->poet)}}&nbsp;&nbsp;<a href="{{$poem->url}}">{{$poem->title}}</a>&nbsp;&nbsp;@lang('poem.edit history')</h2>
<ol class="contribution collapsed">
    @foreach($poem->activityLogs as $key=>$log)
        <li @if($key!==0 && $key!==count($poem->activityLogs)-1)
            class="log-middle"
            @endif>
            @php
                $newVal = $log->properties->get('attributes');
                $oldVal = $log->properties->get('old');
                $props = array_keys($newVal ?? []);

                //dd($log);
            @endphp
            <span title="{{$log->created_at}} UTC">{{\Illuminate\Support\Carbon::parse($log->created_at)->format('Y-m-d')}}</span>&nbsp;
            <span>{{get_causer_name($log)}}</span>&nbsp;
            <span>{{trans('poem.change type '.$log->description)}}</span>


            @if($log->description === 'updated')
                @foreach($props as $prop)
                    @if($prop === 'content_id' or $prop === 'need_confirm')
                        @continue
                    @endif
                    <br>
                    @if($prop === 'poem')
                        <span class="field">{{trans('admin.poem.columns.'.$prop)}}</span>
                    @elseif($prop === 'content_id')

                    @elseif($prop === 'language_id')
                        <span class="field">{{trans('admin.poem.columns.language_id')}}</span>&nbsp;[&nbsp;<del>{{$oldVal[$prop] ? $languageList[$oldVal[$prop]]->name_lang : ''}}</del>&nbsp;->&nbsp;{{$newVal[$prop] ? $languageList[$newVal[$prop]]->name_lang : ''}}&nbsp;]
                    @elseif($prop === 'original_id')
                        <span class="field">{{trans('poem.original poem')}}</span>
                    @else
                        <span class="field">{{trans('admin.poem.columns.'.$prop)}}</span>&nbsp;[&nbsp;<del>{{$oldVal[$prop]}}</del>&nbsp;->&nbsp;{{$newVal[$prop]}}&nbsp;]
                    @endif
                @endforeach
            @elseif($log->description === 'created')
                @lang('poem.initial version')
            @endif
        </li>
    @endforeach

    @if(count($poem->activityLogs)<1 or $poem->activityLogs->last()->description !== 'created')
        <li title="{{$poem->created_at}}"><span class="field">@lang('poem.initial upload')</span> PoemWiki</li>
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
    }
  </style>
@endpush
