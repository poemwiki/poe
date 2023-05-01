@extends('layouts.form')

@php
  /** @var \App\Models\Poem $poem */
@endphp
@section('title', trans('admin.poem.actions.create'))
{{--@section('poemTitle', $poem->title)--}}

@section('form')

  <div class="container-xl">
    <div class="card">

      <poem-form
        id="poem-form"
        :action="'{{ url('poems/store') }}'"
        @if($mode === 'create original')
          :data="{{ $poem->toFillableJson(0, ['#user_name', '#translated_id']) }}"
        @else
          :data="{{ $poem->toFillableJson(0, ['#user_name']) }}"
        @endif
        :trans="{{json_encode($trans)}}"
        :locales="{{ json_encode($locales) }}"
        :default-authors="{{ json_encode($defaultAuthors) }}"
        v-cloak
        inline-template>

        <form class="form-horizontal form-create wiki-form" method="post" @submit.prevent="onSubmit" :action="action" novalidate>

          <div class="card-header">
            <i class="fa fa-plus"></i>
            @if($translatedPoem)
              添加&nbsp;&nbsp;<a target="_blank" href="{{$translatedPoem->url}}">{{ $translatedPoem->title }}</a>&nbsp;&nbsp;的原作
            @elseif($originalPoem)
              添加&nbsp;&nbsp;<a target="_blank" href="{{$originalPoem->url}}">{{ $originalPoem->title }}</a>&nbsp;&nbsp;的翻译版本
            @else
              {{ trans('admin.poem.actions.create') }}
            @endif
          </div>

          <div class="card-body">
            @include('poems.components.form-elements', ['mode' => $mode])
          </div>

          <div class="mt-8 card-footer text-right">
{{--            <button type="submit" class="btn btn-wire" v-if="form.poet">--}}
{{--              <i class="fa" :class="submiting ? 'fa-spinner' : 'fa-download'"></i>--}}
{{--              @{{ submiting ? lang.Saving : lang.Submit }}并继续添加 @{{form.poet}} 的作品--}}
{{--            </button>--}}
            <button type="submit" class="btn btn-wire" :disabled="submiting">
              <i class="fa" :class="submiting ? 'fa-spinner' : 'fa-download'"></i>
              @{{ submiting ? lang.Saving : lang.Submit }}
            </button>
          </div>

        </form>

      </poem-form>

    </div>

  </div>


@endsection

@push('scripts')
  <script src="{{ mix('/js/poem.js') }}"></script>
@endpush