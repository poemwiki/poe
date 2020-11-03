@extends('brackets/admin-ui::admin.layout.default')

@section('title', trans('admin.category.actions.edit', ['name' => $category->name]))

@section('body')

    <div class="container-xl">
        <div class="card">

            <category-form
                :action="'{{ $category->resource_url }}'"
                :data="{{ $category->toJsonAllLocales() }}"
                :locales="{{ json_encode($locales) }}"
                :send-empty-locales="false"
                v-cloak
                inline-template>
            
                <form class="form-horizontal form-edit" method="post" @submit.prevent="onSubmit" :action="action" novalidate>


                    <div class="card-header">
                        <i class="fa fa-pencil"></i> {{ trans('admin.category.actions.edit', ['name' => $category->name]) }}
                    </div>

                    <div class="card-body">
                        @include('admin.category.components.form-elements')
                    </div>
                    
                    
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" :disabled="submiting">
                            <i class="fa" :class="submiting ? 'fa-spinner' : 'fa-download'"></i>
                            {{ trans('brackets/admin-ui::admin.btn.save') }}
                        </button>
                    </div>
                    
                </form>

        </category-form>

        </div>
    
</div>

@endsection