<div class="form-group row align-items-center" :class="{'has-danger': errors.has('title'), 'has-success': fields.title && fields.title.valid }">
    <label for="title" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.title') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.title" v-validate="''" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('title'), 'form-control-success': fields.title && fields.title.valid}" id="title" name="title" placeholder="{{ trans('admin.poem.columns.title') }}">
        <div v-if="errors.has('title')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('title') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('from'), 'has-success': fields.from && fields.from.valid }">
    <label for="from" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.from') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.from" v-validate="''" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('from'), 'form-control-success': fields.from && fields.from.valid}" id="from" name="from" placeholder="{{ trans('admin.poem.columns.from') }}">
        <div v-if="errors.has('from')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('from') }}</div>
    </div>
</div>

<div class="form-check row" :class="{'has-danger': errors.has('is_lock'), 'has-success': fields.is_lock && fields.is_lock.valid }">
    <div class="ml-md-auto" :class="isFormLocalized ? 'col-md-8' : 'col-md-10'">
        <input class="form-check-input" id="is_lock" type="checkbox" v-model="form.is_lock" v-validate="''" data-vv-name="is_lock"  name="is_lock_fake_element">
        <label class="form-check-label" for="is_lock">
            {{ trans('admin.poem.columns.is_lock') }}
        </label>
        <input type="hidden" name="is_lock" :value="form.is_lock">
        <div v-if="errors.has('is_lock')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('is_lock') }}</div>
    </div>
</div>

<div class="form-check row" :class="{'has-danger': errors.has('need_confirm'), 'has-success': fields.need_confirm && fields.need_confirm.valid }">
    <div class="ml-md-auto" :class="isFormLocalized ? 'col-md-8' : 'col-md-10'">
        <input class="form-check-input" id="need_confirm" type="checkbox" v-model="form.need_confirm" v-validate="''" data-vv-name="need_confirm"  name="need_confirm_fake_element">
        <label class="form-check-label" for="need_confirm">
            {{ trans('admin.poem.columns.need_confirm') }}
        </label>
        <input type="hidden" name="need_confirm" :value="form.need_confirm">
        <div v-if="errors.has('need_confirm')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('need_confirm') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('nation'), 'has-success': fields.nation && fields.nation.valid }">
    <label for="nation" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.nation') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.nation" v-validate="''" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('nation'), 'form-control-success': fields.nation && fields.nation.valid}" id="nation" name="nation" placeholder="{{ trans('admin.poem.columns.nation') }}">
        <div v-if="errors.has('nation')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('nation') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('dynasty'), 'has-success': fields.dynasty && fields.dynasty.valid }">
    <label for="dynasty" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.dynasty') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.dynasty" v-validate="''" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('dynasty'), 'form-control-success': fields.dynasty && fields.dynasty.valid}" id="dynasty" name="dynasty" placeholder="{{ trans('admin.poem.columns.dynasty') }}">
        <div v-if="errors.has('dynasty')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('dynasty') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('date'), 'has-success': fields.date && fields.date.valid }">
    <label for="date" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.date') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.date" v-validate="''" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('date'), 'form-control-success': fields.date && fields.date.valid}" id="date" name="date" placeholder="{{ trans('admin.poem.columns.date') }}">
        <div v-if="errors.has('date')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('date') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('month'), 'has-success': fields.month && fields.month.valid }">
    <label for="month" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.month') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.month" v-validate="''" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('month'), 'form-control-success': fields.month && fields.month.valid}" id="month" name="month" placeholder="{{ trans('admin.poem.columns.month') }}">
        <div v-if="errors.has('month')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('month') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('year'), 'has-success': fields.year && fields.year.valid }">
    <label for="year" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.year') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.year" v-validate="''" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('year'), 'form-control-success': fields.year && fields.year.valid}" id="year" name="year" placeholder="{{ trans('admin.poem.columns.year') }}">
        <div v-if="errors.has('year')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('year') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('translator'), 'has-success': fields.translator && fields.translator.valid }">
    <label for="translator" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.translator') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.translator" v-validate="''" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('translator'), 'form-control-success': fields.translator && fields.translator.valid}" id="translator" name="translator" placeholder="{{ trans('admin.poem.columns.translator') }}">
        <div v-if="errors.has('translator')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('translator') }}</div>
    </div>
</div>

<div class="form-check row" :class="{'has-danger': errors.has('language'), 'has-success': fields.language && fields.language.valid }">
    <div class="ml-md-auto" :class="isFormLocalized ? 'col-md-8' : 'col-md-10'">
        <input class="form-check-input" id="language" type="checkbox" v-model="form.language" v-validate="''" data-vv-name="language"  name="language_fake_element">
        <label class="form-check-label" for="language">
            {{ trans('admin.poem.columns.language') }}
        </label>
        <input type="hidden" name="language" :value="form.language">
        <div v-if="errors.has('language')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('language') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('length'), 'has-success': fields.length && fields.length.valid }">
    <label for="length" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.length') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.length" v-validate="''" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('length'), 'form-control-success': fields.length && fields.length.valid}" id="length" name="length" placeholder="{{ trans('admin.poem.columns.length') }}">
        <div v-if="errors.has('length')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('length') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('poem'), 'has-success': fields.poem && fields.poem.valid }">
    <label for="poem" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.poem') }}</label>
    <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <div>
            <textarea class="form-control" v-model="form.poem" v-validate="''" id="poem" name="poem"></textarea>
        </div>
        <div v-if="errors.has('poem')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('poem') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('bedtime_post_title'), 'has-success': fields.bedtime_post_title && fields.bedtime_post_title.valid }">
    <label for="bedtime_post_title" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.bedtime_post_title') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.bedtime_post_title" v-validate="''" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('bedtime_post_title'), 'form-control-success': fields.bedtime_post_title && fields.bedtime_post_title.valid}" id="bedtime_post_title" name="bedtime_post_title" placeholder="{{ trans('admin.poem.columns.bedtime_post_title') }}">
        <div v-if="errors.has('bedtime_post_title')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('bedtime_post_title') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('bedtime_post_id'), 'has-success': fields.bedtime_post_id && fields.bedtime_post_id.valid }">
    <label for="bedtime_post_id" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.bedtime_post_id') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.bedtime_post_id" v-validate="''" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('bedtime_post_id'), 'form-control-success': fields.bedtime_post_id && fields.bedtime_post_id.valid}" id="bedtime_post_id" name="bedtime_post_id" placeholder="{{ trans('admin.poem.columns.bedtime_post_id') }}">
        <div v-if="errors.has('bedtime_post_id')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('bedtime_post_id') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('poet_cn'), 'has-success': fields.poet_cn && fields.poet_cn.valid }">
    <label for="poet_cn" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.poet_cn') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.poet_cn" v-validate="''" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('poet_cn'), 'form-control-success': fields.poet_cn && fields.poet_cn.valid}" id="poet_cn" name="poet_cn" placeholder="{{ trans('admin.poem.columns.poet_cn') }}">
        <div v-if="errors.has('poet_cn')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('poet_cn') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('poet'), 'has-success': fields.poet && fields.poet.valid }">
    <label for="poet" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.poet') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.poet" v-validate="''" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('poet'), 'form-control-success': fields.poet && fields.poet.valid}" id="poet" name="poet" placeholder="{{ trans('admin.poem.columns.poet') }}">
        <div v-if="errors.has('poet')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('poet') }}</div>
    </div>
</div>

<div class="form-check row" :class="{'has-danger': errors.has('is_original'), 'has-success': fields.is_original && fields.is_original.valid }">
    <div class="ml-md-auto" :class="isFormLocalized ? 'col-md-8' : 'col-md-10'">
        <input class="form-check-input" id="is_original" type="checkbox" v-model="form.is_original" v-validate="''" data-vv-name="is_original"  name="is_original_fake_element">
        <label class="form-check-label" for="is_original">
            {{ trans('admin.poem.columns.is_original') }}
        </label>
        <input type="hidden" name="is_original" :value="form.is_original">
        <div v-if="errors.has('is_original')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('is_original') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('content_id'), 'has-success': fields.content_id && fields.content_id.valid }">
    <label for="content_id" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.content_id') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.content_id" v-validate="'integer'" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('content_id'), 'form-control-success': fields.content_id && fields.content_id.valid}" id="content_id" name="content_id" placeholder="{{ trans('admin.poem.columns.content_id') }}">
        <div v-if="errors.has('content_id')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('content_id') }}</div>
    </div>
</div>


