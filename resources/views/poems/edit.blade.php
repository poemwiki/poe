@extends('layouts.form')

<?php /** @var \App\Models\Poem $poem */ ?>
@section('title', trans('admin.poem.actions.edit'))
@section('poemTitle', $poem->title)

@section('body')

    <div class="container-xl">
        <div class="card">

            <poem-form
                :action="'{{ route('poems/update', [$poem->fake_id]) }}'"
                :data="{{ $poem->toJson() }}"
                v-cloak
                inline-template>

                <form class="form-horizontal form-edit" method="post" @submit.prevent="onSubmit" :action="action" novalidate>

                    <div class="card-header">
                        <i class="fa fa-pencil"></i> {{ trans('admin.poem.actions.edit', ['name' => $poem->title]) }}
                    </div>

                    <div class="card-body">
                        @include('poems.components.form-elements')
                    </div>


                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" :disabled="submiting">
                            <i class="fa" :class="submiting ? 'fa-spinner' : 'fa-download'"></i>
                            {{ trans('brackets/admin-ui::admin.btn.save') }}
                        </button>
                    </div>

                </form>

        </poem-form>

        </div>

</div>

@endsection
