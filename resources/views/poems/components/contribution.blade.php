<ol class="contribution collapsed">
    @foreach($logs as $key=>$log)
        <li @if($key!==0 && $key!==count($logs)-1)
            class="log-middle"
            @endif><span title="{{$log->created_at}} UTC">{{\Illuminate\Support\Carbon::parse($log->created_at)->format('Y-m-d')}}</span> {{$log->causer_type === "App\User" ? \App\User::find($log->causer_id)->name : '系统'}} {{trans('poem.change type '.$log->description)}}
            @php
                $newVal = $log->properties->get('attributes');
                $oldVal = $log->properties->get('old');
                $props = array_keys($newVal);
                //dd($props);
            @endphp

            @if($log->description === 'updated')
                @foreach($props as $prop)
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
        @if($key === 0 && count($logs) > 2)
            <a id="folder" class="btn">...</a>
        @endif
    @endforeach
</ol>
