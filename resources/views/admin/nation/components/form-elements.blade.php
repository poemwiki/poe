<div class="row form-inline" style="padding-bottom: 10px;" v-cloak>
    <div :class="{'col-xl-10 col-md-11 text-right': !isFormLocalized, 'col text-center': isFormLocalized, 'hidden': onSmallScreen }">
        <small>{{ trans('brackets/admin-ui::admin.forms.currently_editing_translation') }}<span v-if="!isFormLocalized && otherLocales.length > 1"> {{ trans('brackets/admin-ui::admin.forms.more_can_be_managed') }}</span><span v-if="!isFormLocalized"> | <a href="#" @click.prevent="showLocalization">{{ trans('brackets/admin-ui::admin.forms.manage_translations') }}</a></span></small>
        <i class="localization-error" v-if="!isFormLocalized && showLocalizedValidationError"></i>
    </div>

    <div class="col text-center" :class="{'language-mobile': onSmallScreen, 'has-error': !isFormLocalized && showLocalizedValidationError}" v-if="isFormLocalized || onSmallScreen" v-cloak>
        <small>{{ trans('brackets/admin-ui::admin.forms.choose_translation_to_edit') }}
            <select class="form-control" v-model="currentLocale">
                <option :value="defaultLocale" v-if="onSmallScreen">@{{defaultLocale.toUpperCase()}}</option>
                <option v-for="locale in otherLocales" :value="locale">@{{locale.toUpperCase()}}</option>
            </select>
            <i class="localization-error" v-if="isFormLocalized && showLocalizedValidationError"></i>
            <span>|</span>
            <a href="#" @click.prevent="hideLocalization">{{ trans('brackets/admin-ui::admin.forms.hide') }}</a>
        </small>
    </div>
</div>

<div class="row">
    @foreach($locales as $locale)
        <div class="col-md" v-show="shouldShowLangGroup('{{ $locale }}')" v-cloak>
            <div class="form-group row align-items-center" :class="{'has-danger': errors.has('describe_lang_{{ $locale }}'), 'has-success': fields['describe_lang_{{ $locale }}'] && fields['describe_lang_{{ $locale }}'].valid }">
                <label for="describe_lang_{{ $locale }}" class="col-md-2 col-form-label text-md-right">{{ trans('admin.nation.columns.describe_lang') }}</label>
                <div class="col-md-9" :class="{'col-xl-8': !isFormLocalized }">
                    <input type="text" v-model="form.describe_lang['{{ $locale }}']" v-validate="''" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('describe_lang_{{ $locale }}'), 'form-control-success': fields['describe_lang_{{ $locale }}'] && fields['describe_lang_{{ $locale }}'].valid }" id="describe_lang_{{ $locale }}" name="describe_lang_{{ $locale }}" placeholder="{{ trans('admin.nation.columns.describe_lang') }}">
                    <div v-if="errors.has('describe_lang_{{ $locale }}')" class="form-control-feedback form-text" v-cloak>{{'{{'}} errors.first('describe_lang_{{ $locale }}') }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row">
    @foreach($locales as $locale)
        <div class="col-md" v-show="shouldShowLangGroup('{{ $locale }}')" v-cloak>
            <div class="form-group row align-items-center" :class="{'has-danger': errors.has('name_lang_{{ $locale }}'), 'has-success': fields['name_lang_{{ $locale }}'] && fields['name_lang_{{ $locale }}'].valid }">
                <label for="name_lang_{{ $locale }}" class="col-md-2 col-form-label text-md-right">{{ trans('admin.nation.columns.name_lang') }}</label>
                <div class="col-md-9" :class="{'col-xl-8': !isFormLocalized }">
                    <input type="text" v-model="form.name_lang['{{ $locale }}']" v-validate="'required'" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('name_lang_{{ $locale }}'), 'form-control-success': fields['name_lang_{{ $locale }}'] && fields['name_lang_{{ $locale }}'].valid }" id="name_lang_{{ $locale }}" name="name_lang_{{ $locale }}" placeholder="{{ trans('admin.nation.columns.name_lang') }}">
                    <div v-if="errors.has('name_lang_{{ $locale }}')" class="form-control-feedback form-text" v-cloak>{{'{{'}} errors.first('name_lang_{{ $locale }}') }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('f_id'), 'has-success': fields.f_id && fields.f_id.valid }">
    <label for="f_id" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.nation.columns.f_id') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.f_id" v-validate="'required'" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('f_id'), 'form-control-success': fields.f_id && fields.f_id.valid}" id="f_id" name="f_id" placeholder="{{ trans('admin.nation.columns.f_id') }}">
        <div v-if="errors.has('f_id')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('f_id') }}</div>
    </div>
</div>


<div class="form-group row align-items-center" :class="{'has-danger': errors.has('wikidata_id'), 'has-success': fields.wikidata_id && fields.wikidata_id.valid }">
    <label for="wikidata_id" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.nation.columns.wikidata_id') }}</label>
    <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <div>
            <textarea class="form-control" v-model="form.wikidata_id" v-validate="''" id="wikidata_id" name="wikidata_id"></textarea>
        </div>
        <div v-if="errors.has('wikidata_id')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('wikidata_id') }}</div>
    </div>
</div>


