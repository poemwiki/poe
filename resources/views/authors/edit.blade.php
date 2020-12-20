@extends('layouts.fe-form')

@php
/** @var \App\Models\Author $author */
@endphp
@section('title', trans('admin.author.actions.edit') .' - '.$author->name_lang )

@section('form')

  <div class="container-xl">
    <div class="card">

      <author-form
        class="wiki-form"
        :action="'{{ route('author/update', [$author->fake_id]) }}'"
        :data="{{ $author->toJsonAllLocales()}}"
        :locales="{{ json_encode($locales) }}"
        :trans="{{json_encode($trans)}}"
        v-cloak
        inline-template>

        <form class="form-horizontal form-edit" method="post" @submit.prevent="onSubmit" :action="action"
              novalidate>

          <div class="card-header">
            <i class="fa fa-pencil"></i> {{ trans('admin.author.actions.edit') }}&emsp;
            <a target="_blank" href="{{$author->url}}">{{ $author->name_lang }}</a>
          </div>

          <div class="card-body">
            @include('authors.components.form-elements')
          </div>

          <div class="card-footer text-right">
            <button type="submit" class="btn btn-wire" :disabled="submiting">
              <i class="fa" :class="submiting ? 'fa-spinner' : 'fa-download'"></i>
              @{{ submiting ? lang.Saving : lang.Save }}
            </button>
          </div>

        </form>

      </author-form>

    </div>

  </div>

@endsection

@push('scripts')
  <script src="{{ mix('/js/author.js') }}"></script>
@endpush