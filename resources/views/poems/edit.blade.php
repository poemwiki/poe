@extends('layouts.form')

<?php /** @var \App\Models\Poem $poem */ ?>
@section('title', trans('admin.poem.actions.edit'))
@section('poemTitle', $poem->title)

@section('body')

    <div class="container-xl">
        <div class="card">

            <poem-form
                :action="'{{ route('poems/update', [$poem->fake_id]) }}'"
                :data="{{ $poem->toJson() /*TODO pass fillable attributes only*/}}"
                v-cloak
                inline-template>

                <form class="form-horizontal form-edit" method="post" @submit.prevent="onSubmit" :action="action" novalidate>

                    <div class="card-header">
                        <i class="fa fa-pencil"></i> {{ trans('admin.poem.actions.edit', ['name' => $poem->title]) }}

                        &nbsp;&nbsp;{{$poem->poet}} 《<a target="_blank" href="{{$poem->url}}">{{ $poem->title }}</a>》
                    </div>

                    <div class="card-body">
                        @include('poems.components.form-elements')
                    </div>


                    <div class="card-footer text-right">
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
