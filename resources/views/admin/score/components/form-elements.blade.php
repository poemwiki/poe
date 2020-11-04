<div class="form-group row align-items-center" :class="{'has-danger': errors.has('poem_id'), 'has-success': fields.poem_id && fields.poem_id.valid }">
    <label for="poem_id" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.score.columns.poem_id') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.poem_id" v-validate="'required'" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('poem_id'), 'form-control-success': fields.poem_id && fields.poem_id.valid}" id="poem_id" name="poem_id" placeholder="{{ trans('admin.score.columns.poem_id') }}">
        <div v-if="errors.has('poem_id')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('poem_id') }}</div>
    </div>
</div>

<div class="form-check row" :class="{'has-danger': errors.has('score'), 'has-success': fields.score && fields.score.valid }">
    <div class="ml-md-auto" :class="isFormLocalized ? 'col-md-8' : 'col-md-10'">
        <input class="form-check-input" id="score" type="checkbox" v-model="form.score" v-validate="''" data-vv-name="score"  name="score_fake_element">
        <label class="form-check-label" for="score">
            {{ trans('admin.score.columns.score') }}
        </label>
        <input type="hidden" name="score" :value="form.score">
        <div v-if="errors.has('score')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('score') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('user_id'), 'has-success': fields.user_id && fields.user_id.valid }">
    <label for="user_id" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.score.columns.user_id') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.user_id" v-validate="'required'" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('user_id'), 'form-control-success': fields.user_id && fields.user_id.valid}" id="user_id" name="user_id" placeholder="{{ trans('admin.score.columns.user_id') }}">
        <div v-if="errors.has('user_id')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('user_id') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('weight'), 'has-success': fields.weight && fields.weight.valid }">
    <label for="weight" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.score.columns.weight') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.weight" v-validate="'required|decimal'" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('weight'), 'form-control-success': fields.weight && fields.weight.valid}" id="weight" name="weight" placeholder="{{ trans('admin.score.columns.weight') }}">
        <div v-if="errors.has('weight')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('weight') }}</div>
    </div>
</div>


