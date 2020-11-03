@extends('brackets/admin-ui::admin.layout.default')

@section('title', trans('admin.author.actions.edit', ['name' => $author->id]))

@section('body')

    <div class="container-xl">
        <div class="card">

            <author-form
                :action="'{{ $author->resource_url }}'"
                :data="{{ $author->toJsonAllLocales() }}"
                :locales="{{ json_encode($locales) }}"
                :send-empty-locales="false"
                v-cloak
                inline-template>
            
                <form class="form-horizontal form-edit" method="post" @submit.prevent="onSubmit" :action="action" novalidate>


                    <div class="card-header">
                        <i class="fa fa-pencil"></i> {{ trans('admin.author.actions.edit', ['name' => $author->id]) }}
                    </div>

                    <div class="card-body">
                        @include('admin.author.components.form-elements')
                    </div>
                    
                    
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" :disabled="submiting">
                            <i class="fa" :class="submiting ? 'fa-spinner' : 'fa-download'"></i>
                            {{ trans('brackets/admin-ui::admin.btn.save') }}
                        </button>
                    </div>
                    
                </form>

        </author-form>

        </div>
    
</div>

@endsection