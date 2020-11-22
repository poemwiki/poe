

<div class="form-check row" :class="{'has-danger': errors.has('is_v'), 'has-success': fields.is_v && fields.is_v.valid }">
    <div class="ml-md-auto" :class="isFormLocalized ? 'col-md-8' : 'col-md-10'">
        <input class="form-check-input" id="is_v" type="checkbox" v-model="form.is_v" v-validate="''" data-vv-name="is_v"  name="is_v_fake_element">
        <label class="form-check-label" for="is_v">
            {{ trans('admin.user.columns.is_v') }}
        </label>
        <input type="hidden" name="is_v" :value="form.is_v">
        <div v-if="errors.has('is_v')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('is_v') }}</div>
    </div>
</div>