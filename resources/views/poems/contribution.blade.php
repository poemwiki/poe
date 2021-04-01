@extends('layouts.fe')


@section('title'){{$poem->title}}@endsection
@section('author')
    PoemWiki
@endsection
@section('content')
<h2>{{($poem->poet_cn ?? $poem->poet)}}  <a href="{{$poem->url}}">{{$poem->title}}</a> @lang('poem.edit history')</h2>
<ol class="contribution collapsed">
    @foreach($poem->activityLogs as $key=>$log)
        <li @if($key!==0 && $key!==count($poem->activityLogs)-1)
            class="log-middle"
            @endif>
            @php
                $newVal = $log->properties->get('attributes');
                $oldVal = $log->properties->get('old');
                $props = array_keys($newVal ?? []);
                //dd($logs);
            @endphp
            <span title="{{$log->created_at}} UTC">{{\Illuminate\Support\Carbon::parse($log->created_at)->format('Y-m-d')}}</span> {{$log->causer_type === "App\User" ? \App\User::find($log->causer_id)->name : '系统'}} {{trans('poem.change type '.$log->description)}}

            @if($log->description === 'updated')
                @foreach($props as $prop)
                    @if($prop === 'content_id' or $prop === 'need_confirm')
                        @continue
                    @endif
                    <br>
                    @if($prop === 'poem')
                        <span class="field">{{trans('admin.poem.columns.'.$prop)}}</span>
                    @elseif($prop === 'content_id')

                    @elseif($prop === 'original_id')
                        <span class="field">{{trans('poem.original poem')}}</span>
                    @else
                        <span class="field">{{trans('admin.poem.columns.'.$prop)}}</span> [ <del>{{$oldVal[$prop]}}</del> -> {{$newVal[$prop]}} ]
                    @endif
                @endforeach
            @elseif($log->description === 'created')
                @lang('poem.initial version')
            @endif
        </li>
    @endforeach

re    @if(count($poem->activityLogs)<1)
        <li title="{{$poem->created_at}}"><span class="field">@lang('poem.initial upload')</span> PoemWiki</li>
    @endif
</ol>
@endsection

@push('styles')
  <style>
    .field{
      display: inline-block;
      min-width: 6em;
    }
  </style>
@endpush
