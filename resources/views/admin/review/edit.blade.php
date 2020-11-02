@extends('brackets/admin-ui::admin.layout.default')

@section('title', trans('admin.review.actions.edit', ['name' => $review->title]))

@section('body')

    <div class="container-xl">
        <div class="card">

            <review-form
                :action="'{{ $review->resource_url }}'"
                :data="{{ $review->toJson() }}"
                v-cloak
                inline-template>
            
                <form class="form-horizontal form-edit" method="post" @submit.prevent="onSubmit" :action="action" novalidate>


                    <div class="card-header">
                        <i class="fa fa-pencil"></i> {{ trans('admin.review.actions.edit', ['name' => $review->title]) }}
                    </div>

                    <div class="card-body">
                        @include('admin.review.components.form-elements')
                    </div>
                    
                    
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" :disabled="submiting">
                            <i class="fa" :class="submiting ? 'fa-spinner' : 'fa-download'"></i>
                            {{ trans('brackets/admin-ui::admin.btn.save') }}
                        </button>
                    </div>
                    
                </form>

        </review-form>

        </div>
    
</div>

@endsection