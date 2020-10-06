@extends('layouts.fe')


@section('title'){{$poem->title}}@endsection
@section('author')
    PoemWiki
@endsection

@section('content')
<h2>{{($poem->poet_cn ?? $poem->poet)}}  <a href="{{$poem->url}}">{{$poem->title}}</a> @lang('poem.edit history')</h2>
<ol class="contribution collapsed">
    @foreach($logs as $key=>$log)
        <li @if($key!==0 && $key!==count($logs)-1)
            class="log-middle"
            @endif>
            @php
                $newVal = $log->properties->get('attributes');
                $oldVal = $log->properties->get('old');
                $props = array_keys($newVal ?? []);
            @endphp
            <span title="{{$log->created_at}} UTC">{{\Illuminate\Support\Carbon::parse($log->created_at)->format('Y-m-d')}}</span> {{$log->causer_type === "App\User" ? \App\User::find($log->causer_id)->name : '系统'}} {{trans('poem.change type '.$log->description)}}

            @if($log->description === 'updated')
                @foreach($props as $prop)
                    @if($prop === 'content_id')
                        @continue
                    @endif
                    <br>
                    @if($prop === 'poem')
                        {{trans('admin.poem.columns.'.$prop)}}
                    @elseif($prop === 'content_id')

                    @elseif($prop === 'original_id')
                        {{trans('poem.original poem')}}
                    @else
                        {{trans('admin.poem.columns.'.$prop)}} [ <del>{{$oldVal[$prop]}}</del> -> {{$newVal[$prop]}} ]
                    @endif
                @endforeach
            @elseif($log->description === 'created')
                @lang('poem.initial version')
            @endif
        </li>
    @endforeach

    @if(count($logs)<=1)
        <li title="{{$poem->created_at}}">@lang('poem.initial upload') PoemWiki</li>
    @endif
</ol>
@endsection
