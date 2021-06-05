@extends('layouts.fe-form')

@php
  /** @var \App\Models\Poem $poem */
@endphp
@section('title', trans('admin.poem.actions.edit') .' - '.$poem->title )

@section('form')

  <div class="container-xl">
    <div class="card">

      <poem-form
        id="poem-form"
        :action="'{{ route('poems/update', [$poem->fake_id]) }}'"
        :data="{{ $poem->toFillableJson() }}"
        :locales="{{ json_encode($locales) }}"
        :trans="{{json_encode($trans)}}"
        :default-authors="{{ json_encode($defaultAuthors) }}"
        v-cloak
        inline-template>

        <form class="form-horizontal form-edit wiki-form" method="post" @submit.prevent="onSubmit" :action="action"
              novalidate>

          <div class="card-header">
            <i class="fa fa-pencil"></i> {{ trans('admin.poem.actions.edit', ['name' => $poem->title]) }}&nbsp;&nbsp;{{$poem->poetAuthor ? $poem->poetAuthor->label : $poem->poet_cn ?? $poem->poet ?? ''}}&nbsp;&nbsp;<a target="_blank" href="{{$poem->url}}">{{ $poem->title }}</a>
          </div>

          <div class="card-body">
            @include('poems.components.form-elements', ['mode' => 'edit'])
          </div>


          <div class="mt-8 card-footer text-right">
            <button type="submit" class="btn btn-wire" :disabled="submiting">
              <i class="fa" :class="submiting ? 'fa-spinner' : 'fa-download'"></i>
              @{{ submiting ? lang.Saving : lang.Save }}
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