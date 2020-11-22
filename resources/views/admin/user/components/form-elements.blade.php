<div class="form-group row align-items-center" :class="{'has-danger': errors.has('avatar'), 'has-success': fields.avatar && fields.avatar.valid }">
    <label for="avatar" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.user.columns.avatar') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.avatar" v-validate="''" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('avatar'), 'form-control-success': fields.avatar && fields.avatar.valid}" id="avatar" name="avatar" placeholder="{{ trans('admin.user.columns.avatar') }}">
        <div v-if="errors.has('avatar')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('avatar') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('email'), 'has-success': fields.email && fields.email.valid }">
    <label for="email" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.user.columns.email') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.email" v-validate="'required|email'" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('email'), 'form-control-success': fields.email && fields.email.valid}" id="email" name="email" placeholder="{{ trans('admin.user.columns.email') }}">
        <div v-if="errors.has('email')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('email') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('email_verified_at'), 'has-success': fields.email_verified_at && fields.email_verified_at.valid }">
    <label for="email_verified_at" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.user.columns.email_verified_at') }}</label>
    <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <div class="input-group input-group--custom">
            <div class="input-group-addon"><i class="fa fa-calendar"></i></div>
            <datetime v-model="form.email_verified_at" :config="datetimePickerConfig" v-validate="'date_format:yyyy-MM-dd HH:mm:ss'" class="flatpickr" :class="{'form-control-danger': errors.has('email_verified_at'), 'form-control-success': fields.email_verified_at && fields.email_verified_at.valid}" id="email_verified_at" name="email_verified_at" placeholder="{{ trans('brackets/admin-ui::admin.forms.select_date_and_time') }}"></datetime>
        </div>
        <div v-if="errors.has('email_verified_at')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('email_verified_at') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('invite_code'), 'has-success': fields.invite_code && fields.invite_code.valid }">
    <label for="invite_code" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.user.columns.invite_code') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.invite_code" v-validate="'required'" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('invite_code'), 'form-control-success': fields.invite_code && fields.invite_code.valid}" id="invite_code" name="invite_code" placeholder="{{ trans('admin.user.columns.invite_code') }}">
        <div v-if="errors.has('invite_code')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('invite_code') }}</div>
    </div>
</div>

<div class="form-check row" :class="{'has-danger': errors.has('invite_max'), 'has-success': fields.invite_max && fields.invite_max.valid }">
    <div class="ml-md-auto" :class="isFormLocalized ? 'col-md-8' : 'col-md-10'">
        <input class="form-check-input" id="invite_max" type="checkbox" v-model="form.invite_max" v-validate="''" data-vv-name="invite_max"  name="invite_max_fake_element">
        <label class="form-check-label" for="invite_max">
            {{ trans('admin.user.columns.invite_max') }}
        </label>
        <input type="hidden" name="invite_max" :value="form.invite_max">
        <div v-if="errors.has('invite_max')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('invite_max') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('invited_by'), 'has-success': fields.invited_by && fields.invited_by.valid }">
    <label for="invited_by" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.user.columns.invited_by') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.invited_by" v-validate="''" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('invited_by'), 'form-control-success': fields.invited_by && fields.invited_by.valid}" id="invited_by" name="invited_by" placeholder="{{ trans('admin.user.columns.invited_by') }}">
        <div v-if="errors.has('invited_by')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('invited_by') }}</div>
    </div>
</div>

<div class="form-check row" :class="{'has-danger': errors.has('is_active'), 'has-success': fields.is_active && fields.is_active.valid }">
    <div class="ml-md-auto" :class="isFormLocalized ? 'col-md-8' : 'col-md-10'">
        <input class="form-check-input" id="is_active" type="checkbox" v-model="form.is_active" v-validate="''" data-vv-name="is_active"  name="is_active_fake_element">
        <label class="form-check-label" for="is_active">
            {{ trans('admin.user.columns.is_active') }}
        </label>
        <input type="hidden" name="is_active" :value="form.is_active">
        <div v-if="errors.has('is_active')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('is_active') }}</div>
    </div>
</div>

<div class="form-check row" :class="{'has-danger': errors.has('is_admin'), 'has-success': fields.is_admin && fields.is_admin.valid }">
    <div class="ml-md-auto" :class="isFormLocalized ? 'col-md-8' : 'col-md-10'">
        <input class="form-check-input" id="is_admin" type="checkbox" v-model="form.is_admin" v-validate="''" data-vv-name="is_admin"  name="is_admin_fake_element">
        <label class="form-check-label" for="is_admin">
            {{ trans('admin.user.columns.is_admin') }}
        </label>
        <input type="hidden" name="is_admin" :value="form.is_admin">
        <div v-if="errors.has('is_admin')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('is_admin') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('name'), 'has-success': fields.name && fields.name.valid }">
    <label for="name" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.user.columns.name') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.name" v-validate="'required'" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('name'), 'form-control-success': fields.name && fields.name.valid}" id="name" name="name" placeholder="{{ trans('admin.user.columns.name') }}">
        <div v-if="errors.has('name')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('name') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('password'), 'has-success': fields.password && fields.password.valid }">
    <label for="password" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.user.columns.password') }}</label>
    <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="password" v-model="form.password" v-validate="'min:7'" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('password'), 'form-control-success': fields.password && fields.password.valid}" id="password" name="password" placeholder="{{ trans('admin.user.columns.password') }}" ref="password">
        <div v-if="errors.has('password')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('password') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('password_confirmation'), 'has-success': fields.password_confirmation && fields.password_confirmation.valid }">
    <label for="password_confirmation" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.user.columns.password_repeat') }}</label>
    <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="password" v-model="form.password_confirmation" v-validate="'confirmed:password|min:7'" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('password_confirmation'), 'form-control-success': fields.password_confirmation && fields.password_confirmation.valid}" id="password_confirmation" name="password_confirmation" placeholder="{{ trans('admin.user.columns.password') }}" data-vv-as="password">
        <div v-if="errors.has('password_confirmation')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('password_confirmation') }}</div>
    </div>
</div>


