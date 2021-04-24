@extends('admin.layout.default')

@section('title', trans('admin.user.actions.index'))

@section('body')

    <user-listing
        :data="{{ $data->toJson() }}"
        :url="'{{ url('admin/users') }}'"
        inline-template>

        <div class="row">
            <div class="col">
                <div class="card">
                    <div class="card-header">
                        <i class="fa fa-align-justify"></i> {{ trans('admin.user.actions.index') }}
                        <a class="btn btn-primary btn-spinner btn-sm pull-right m-b-0" href="{{ url('admin/users/create') }}" role="button"><i class="fa fa-plus"></i>&nbsp; {{ trans('admin.user.actions.create') }}</a>
                    </div>
                    <div class="card-body" v-cloak>
                        <div class="card-block">
                            <form @submit.prevent="">
                                <div class="row justify-content-md-between">
                                    <div class="col col-lg-7 col-xl-5 form-group">
                                        <div class="input-group">
                                            <input class="form-control" placeholder="{{ trans('brackets/admin-ui::admin.placeholder.search') }}" v-model="search" @keyup.enter="filter('search', $event.target.value)" />
                                            <span class="input-group-append">
                                                <button type="button" class="btn btn-primary" @click="filter('search', search)"><i class="fa fa-search"></i>&nbsp; {{ trans('brackets/admin-ui::admin.btn.search') }}</button>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-sm-auto form-group ">
                                        <select class="form-control" v-model="pagination.state.per_page">

                                            <option value="10">10</option>
                                            <option value="25">25</option>
                                            <option value="100">100</option>
                                        </select>
                                    </div>
                                </div>
                            </form>

                            <table class="table table-hover table-listing">
                                <thead>
                                    <tr>

                                        <th is='sortable' :column="'name'">{{ trans('admin.user.columns.name') }}</th>
                                        <th is='sortable' :column="'id'">{{ trans('id') }}</th>
                                        <th is='sortable' :column="'email'">{{ trans('admin.user.columns.email') }}</th>
                                        <th is='sortable' :column="'bind_names'">{{ trans('admin.user.columns.bind_info') }}</th>
                                        <th is='sortable' :column="'is_admin'">{{ trans('admin.user.columns.is_admin') }}</th>
                                        <th is='sortable' :column="'is_v'">{{ trans('admin.user.columns.is_v') }}</th>
                                        <th is='sortable' :column="'weight'">{{ trans('admin.user.columns.weight') }}</th>
                                        <th is='sortable' :column="'updated_at'">{{ trans('admin.user.columns.updated_at') }}</th>

                                        <th></th>
                                    </tr>

                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in collection" :key="item.id + '_' + item.bind_id" :class="bulkItems[item.id] ? 'bg-bulk' : ''">

                                        <td>@{{ item.name }}</td>
                                        <td>@{{ item.id }}</td>
                                        <td>@{{ item.email }}</td>
                                        <td><span v-for="(bind) in item.binds" :key="bind.id"
                                          >
                                            [@{{ bind.bind_status ? '已绑定' : '已解绑' }}]
                                            [@{{ bindRefType(bind.bind_ref)  }}]
                                            <img :src="bind.avatar" alt="" style="width: 3em; height: 3em; border-radius: 4px; object-fit: cover" />
                                            @{{ bind.nickname }}
                                          <br/></span></td>
                                        <td>@{{ item.is_admin ? 'Yes' : 'No' }}</td>
                                        <td>@{{ item.is_v ? 'Yes' : 'No' }}</td>
                                        <td>@{{ item.weight }}</td>
                                        <td>@{{ item.updated_at | datetime}}</td>

                                        <td>
                                            <div class="row no-gutters">
                                                <div class="col-auto">
                                                    <a class="btn btn-sm btn-spinner btn-info" :href="item.resource_url + '/addV'" title="认证" role="button"><i class="fa fa-user"></i></a>
                                                </div>
                                                <div class="col-auto hidden">
                                                    <a class="btn btn-sm btn-spinner btn-info" :href="item.resource_url + '/edit'" title="{{ trans('brackets/admin-ui::admin.btn.edit') }}" role="button"><i class="fa fa-edit"></i></a>
                                                </div>
                                                <form class="col hidden" @submit.prevent="deleteItem(item.resource_url)">
                                                    <button type="submit" class="btn btn-sm btn-danger" title="{{ trans('brackets/admin-ui::admin.btn.delete') }}"><i class="fa fa-trash-o"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="row" v-if="pagination.state.total > 0">
                                <div class="col-sm">
                                    <span class="pagination-caption">{{ trans('brackets/admin-ui::admin.pagination.overview') }}</span>
                                </div>
                                <div class="col-sm-auto">
                                    <pagination></pagination>
                                </div>
                            </div>

                            <div class="no-items-found" v-if="!collection.length > 0">
                                <i class="icon-magnifier"></i>
                                <h3>{{ trans('brackets/admin-ui::admin.index.no_items') }}</h3>
                                <p>{{ trans('brackets/admin-ui::admin.index.try_changing_items') }}</p>
                                <a class="btn btn-primary btn-spinner" href="{{ url('admin/users/create') }}" role="button"><i class="fa fa-plus"></i>&nbsp; {{ trans('admin.user.actions.create') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </user-listing>

@endsection