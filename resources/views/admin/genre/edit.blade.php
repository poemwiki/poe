@extends('admin.layout.default')

@section('title', trans('admin.genre.actions.edit', ['name' => $genre->name]))

@section('body')

    <div class="container-xl">
        <div class="card">

            <genre-form
                :action="'{{ $genre->resource_url }}'"
                :data="{{ $genre->toJsonAllLocales() }}"
                :locales="{{ json_encode($locales) }}"
                :send-empty-locales="false"
                v-cloak
                inline-template>

                <form class="form-horizontal form-edit" method="post" @submit.prevent="onSubmit" :action="action" novalidate>


                    <div class="card-header">
                        <i class="fa fa-pencil"></i> {{ trans('admin.genre.actions.edit', ['name' => $genre->name]) }}
                    </div>

                    <div class="card-body">
                        @include('admin.genre.components.form-elements')
                    </div>


                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" :disabled="submiting">
                            <i class="fa" :class="submiting ? 'fa-spinner' : 'fa-download'"></i>
                            {{ trans('brackets/admin-ui::admin.btn.save') }}
                        </button>
                    </div>

                </form>

        </genre-form>

        </div>

</div>

@endsection