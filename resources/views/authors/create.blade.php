@extends('layouts.fe-form')

@section('title', trans('Add Author') )

@section('form')

  <div class="container-xl">
    <div class="card">

      <author-form
        class="wiki-form"
        :action="'{{ route('author/store') }}'"
        :locales="{{ json_encode($locales) }}"
        :trans="{{json_encode($trans)}}"
        :default-nation="{{json_encode($defaultNation)}}"
        :dynasty-list="{{json_encode($dynastyList)}}"
        v-cloak
        inline-template>

        <form class="form-horizontal form-edit" method="post" @submit.prevent="onSubmit" :action="action"
              novalidate>

          <div class="card-header">
            <i class="fa fa-pencil"></i> @lang('Add Author')
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