@extends('brackets/admin-ui::admin.layout.default')

@section('title', trans('admin.score.actions.edit', ['name' => $score->id]))

@section('body')

    <div class="container-xl">
        <div class="card">

            <score-form
                :action="'{{ $score->resource_url }}'"
                :data="{{ $score->toJson() }}"
                v-cloak
                inline-template>
            
                <form class="form-horizontal form-edit" method="post" @submit.prevent="onSubmit" :action="action" novalidate>


                    <div class="card-header">
                        <i class="fa fa-pencil"></i> {{ trans('admin.score.actions.edit', ['name' => $score->id]) }}
                    </div>

                    <div class="card-body">
                        @include('admin.score.components.form-elements')
                    </div>
                    
                    
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" :disabled="submiting">
                            <i class="fa" :class="submiting ? 'fa-spinner' : 'fa-download'"></i>
                            {{ trans('brackets/admin-ui::admin.btn.save') }}
                        </button>
                    </div>
                    
                </form>

        </score-form>

        </div>
    
</div>

@endsection