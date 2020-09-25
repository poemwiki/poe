@extends('layouts.form')

@section('title', trans('admin.poem.actions.create'))
{{--@section('poemTitle', $poem->title)--}}

@section('body')

    <div class="container-xl">

                <div class="card">

        <poem-form
            :action="'{{ url('poems/store') }}'"
            v-cloak
            inline-template>

            <form class="form-horizontal form-create" method="post" @submit.prevent="onSubmit" :action="action" novalidate>

                <div class="card-header">
                    <i class="fa fa-plus"></i>
                    @if($translatedPoem)
                        添加 《<a target="_blank" href="{{$translatedPoem->getUrl()}}">{{ $translatedPoem->title }}</a>》 的原作
                    @elseif($originalPoem)
                        添加 《<a target="_blank" href="{{$originalPoem->getUrl()}}">{{ $originalPoem->title }}</a>》 的其他版本
                    @else
                         {{ trans('admin.poem.actions.create') }}
                    @endif
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
