@extends('admin.layout.default')

@section('title', trans('admin.tag.actions.edit', ['name' => $tag->name]))

@section('body')

    <div class="container-xl">
        <div class="card">

            <tag-form
                :action="'{{ $tag->resource_url }}'"
                :data="{{ $tag->toJsonAllLocales() }}"
                :locales="{{ json_encode($locales) }}"
                :categories="{{ json_encode($categories->toArray()) }}"
                :send-empty-locales="false"
                v-cloak
                inline-template>

                <form class="form-horizontal form-edit" method="post" @submit.prevent="onSubmit" :action="action" novalidate>


                    <div class="card-header">
                        <i class="fa fa-pencil"></i> {{ trans('admin.tag.actions.edit', ['name' => $tag->name]) }}
                    </div>

                    <div class="card-body">
                        @include('admin.tag.components.form-elements')
                    </div>


                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" :disabled="submiting">
                            <i class="fa" :class="submiting ? 'fa-spinner' : 'fa-download'"></i>
                            {{ trans('brackets/admin-ui::admin.btn.save') }}
                        </button>
                    </div>

                </form>

        </tag-form>

        </div>

</div>

@endsection