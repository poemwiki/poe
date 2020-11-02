
@if(isset($originalPoem))
    <div class="form-check row">
        <label class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">
            {{ trans('poem.original work') }}
        </label>
        《<a href="{{$originalPoem->url}}">{{ $originalPoem->title }}</a>》
        <input type="hidden" name="original_id" :value="{{$originalPoem->id}}">
    </div>
@endif
@if(isset($translatedPoem))
    <input type="hidden" name="translated_id" :value="{{$translatedPoem->id}}">
@endif

<div class="form-group row"
     :class="{'has-danger': errors.has('title'), 'has-success': fields.title && fields.title.valid }">
    <label for="title" class="col-form-label text-md-right required"
           :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.title') }}</label>
    <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.title"
               v-validate="'required'"
               data-vv-as="{{ trans('admin.poem.columns.title') }}"
               @input="validate($event)" class="form-control"
               :class="{'form-control-danger': errors.has('title'), 'form-control-success': fields.title && fields.title.valid}"
               id="title" name="title" placeholder="">
        <div v-if="errors.has('title')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('title') }}
        </div>
    </div>
</div>

<div class="form-group row"
     :class="{'has-danger': errors.has('preface') }">
    <label for="preface" class="col-form-label text-md-right"
           :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.preface') }}</label>
    <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.preface"
               v-validate="'max:64'"
               data-vv-as="{{ trans('admin.poem.columns.preface') }}"
               @input="validate($event)" class="form-control"
               :class="{'form-control-danger': errors.has('preface'), 'form-control-success': fields.preface && fields.preface.valid}"
               id="preface" name="preface" placeholder="">
        <div v-if="errors.has('preface')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('preface') }}
        </div>
    </div>
</div>

<div class="form-group row"
     :class="{'has-danger': errors.has('subtitle') }">
    <label for="subtitle" class="col-form-label text-md-right"
           :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.subtitle') }}</label>
    <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.subtitle"
               v-validate="'max:32'"
               data-vv-as="{{ trans('admin.poem.columns.subtitle') }}"
               @input="validate($event)" class="form-control"
               :class="{'form-control-danger': errors.has('subtitle'), 'form-control-success': fields.subtitle && fields.subtitle.valid}"
               id="subtitle" name="subtitle" placeholder="">
        <div v-if="errors.has('subtitle')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('subtitle') }}
        </div>
    </div>
</div>

<div class="form-group row"
     :class="{'has-danger': errors.has('poem'), 'has-success': fields.poem && fields.poem.valid }">
    <label for="poem" class="col-form-label text-md-right required"
           :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.poem') }}</label>
    <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <div>
{{--            <textarea class="form-control" v-model="form.poem" v-validate="''" id="poem" name="poem"></textarea>--}}
            <codemirror
                v-validate.disable="'required'" id="poem" name="poem"
                data-vv-name="poem"
                data-vv-as="{{ trans('admin.poem.columns.poem') }}"
                class=""
                ref="cmEditor"
                :value="form.poem"
                :options="cmOptions"
                :class="{'form-control-danger': errors.has('poem'), 'form-control-success': fields.poem && fields.poem.valid}"
                @input="onCmInput"
                @blur="onCmCodeChange"
            />
        </div>
        <div v-if="errors.has('poem')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('poem') }}</div>
    </div>
</div>

<div class="form-group row"
     :class="{'has-danger': errors.has('poet_cn') }">
    <label for="poet_cn" class="col-form-label text-md-right"
           :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.poet_cn') }}</label>
    <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.poet_cn"
               value="{{$originalPoem->poet_cn ?? $translatedPoem->poet_cn ?? ''}}"
               v-validate="''"
               data-vv-as="{{ trans('admin.poem.columns.poet_cn') }}"
               @input="validate($event)" @blur="validate($event)"
               class="form-control"
               :class="{'form-control-danger': errors.has('poet_cn'), 'form-control-success': fields.poet_cn && fields.poet_cn.valid}"
               id="poet_cn" name="poet_cn" placeholder="">
        <div v-if="errors.has('poet_cn')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('poet_cn')
            }}
        </div>
    </div>
</div>

<div class="form-group row"
     :class="{'has-danger': errors.has('poet'), 'has-success': fields.poet && fields.poet.valid }">
    <label for="poet" class="col-form-label text-md-right required"
           :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.poet') }}</label>
    <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.poet"
               value="{{$originalPoem->poet ?? $translatedPoem->poet ?? ''}}"
               v-validate="'required'"
               data-vv-as="{{ trans('admin.poem.columns.poet') }}"
               @input="validate($event)" @blur="validate($event)" class="form-control"
               :class="{'form-control-danger': errors.has('poet'), 'form-control-success': fields.poet && fields.poet.valid}"
               id="poet" name="poet" placeholder="">
        <div v-if="errors.has('poet')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('poet') }}</div>
    </div>
</div>

<div class="form-group row"
     :class="{'has-danger': errors.has('is_original'), 'has-success': fields.is_original && fields.is_original.valid }">
    <label for="is_original" class="col-form-label text-md-right required"
           :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.is_original') }}</label>
    <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <select class="form-control"
                :class="{'form-control-danger': errors.has('is_original'), 'form-control-success': fields.is_original && fields.is_original.valid}"
                id="is_original"
                v-model="form.is_original"
                v-validate="'required'"
                value="{{!empty($originalPoem) ? 0 : (!empty($translatedPoem) ? 1 : '')}}"
                @if(!empty($originalPoem) || !empty($translatedPoem)) disabled @endif
                data-vv-as="{{ trans('admin.poem.columns.is_original') }}" data-vv-name="is_original"
                name="is_original_fake_element">
            <option value="1"
                    :selected="form.is_original==1">{{ trans('admin.poem.is_original_enum.original') }}</option>
            <option value="0"
                    :selected="form.is_original==0">{{ trans('admin.poem.is_original_enum.translated') }}</option>
        </select>
        <input type="hidden" name="is_original" :value="form.is_original">
        <div v-if="errors.has('is_original')" class="form-control-feedback form-text" v-cloak>@{{
            errors.first('is_original') }}
        </div>
    </div>
</div>

<div class="form-group row"
     :class="{'hidden' : form.is_original==1,'has-danger': errors.has('translator') }">
    <label for="translator" class="col-form-label text-md-right"
           :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.translator') }}</label>
    <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.translator"
               v-validate="''"
               data-vv-as="{{ trans('admin.poem.columns.translator') }}"
               @input="validate($event)" class="form-control"
               :class="{'form-control-danger': errors.has('translator'), 'form-control-success': fields.translator && fields.translator.valid}"
               id="translator" name="translator" placeholder="">
        <div v-if="errors.has('translator')" class="form-control-feedback form-text" v-cloak>@{{
            errors.first('translator') }}
        </div>
    </div>
</div>

<div class="form-group row"
     :class="{'has-danger': errors.has('language'), 'has-success': fields.language && fields.language.valid }">
    <label for="language" class="col-form-label text-md-right required"
           :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.language') }}</label>

    <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <select class="form-control"
                :class="{'form-control-danger': errors.has('language'), 'form-control-success': fields.language && fields.language.valid}"
                id="language" v-model="form.language"
                v-validate="'required'"
                data-vv-as="{{ trans('admin.poem.columns.language') }}" data-vv-name="language"
                name="language_fake_element">
            @foreach($languageList as $lang)
                <option value="{{$lang->id}}" :selected="form.language=={{$lang->id}}">{{ $lang->name }}</option>
            @endforeach
        </select>
        <input type="hidden" name="language" :value="form.language">
        <div v-if="errors.has('language')" class="form-control-feedback form-text" v-cloak>@{{
            errors.first('language') }}
        </div>
    </div>
</div>

<div class="form-group row"
     :class="{'has-danger': errors.has('nation') }">
    <label for="nation" class="col-form-label text-md-right"
           :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.nation') }}</label>
    <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.nation"
               v-validate="''"
               value="{{$originalPoem->nation ?? $translatedPoem->nation ?? ''}}"
               data-vv-as="{{ trans('admin.poem.columns.nation') }}"
               @input="validate($event)" class="form-control"
               :class="{'form-control-danger': errors.has('nation'), 'form-control-success': fields.nation && fields.nation.valid}"
               id="nation" name="nation" placeholder="">
        <div v-if="errors.has('nation')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('nation') }}
        </div>
    </div>
</div>

<div class="form-group row"
     :class="{'has-danger': errors.has('dynasty') }">
    <label for="dynasty" class="col-form-label text-md-right"
           :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.dynasty') }}</label>
    <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.dynasty"
               v-validate="''"
               value="{{$originalPoem->dynasty ?? $translatedPoem->dynasty ?? ''}}"
               data-vv-as="{{ trans('admin.poem.columns.dynasty') }}"
               @input="validate($event)" class="form-control"
               :class="{'form-control-danger': errors.has('dynasty'), 'form-control-success': fields.dynasty && fields.dynasty.valid}"
               id="dynasty" name="dynasty" placeholder="">
        <div v-if="errors.has('dynasty')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('dynasty')
            }}
        </div>
    </div>
</div>

<div class="form-group row"
     :class="{'has-danger': errors.has('from') }">
    <label for="from" class="col-form-label text-md-right"
           :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.from') }}</label>
    <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.from"
               v-validate="''"
               value="{{$originalPoem->from ?? $translatedPoem->from ?? ''}}"
               data-vv-as="{{ trans('admin.poem.columns.from') }}"
               @input="validate($event)" class="form-control"
               :class="{'form-control-danger': errors.has('from'), 'form-control-success': fields.from && fields.from.valid}"
               id="from" name="from" placeholder="">
        <div v-if="errors.has('from')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('from') }}</div>
    </div>
</div>

<div class="form-group row"
     :class="{'has-danger': errors.has('year') }">
    <label for="year" class="col-form-label text-md-right"
           :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.time') }}</label>
    <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'"
        style="display: flex; gap: 1em;">
        <input type="text" v-model="form.year"
               v-validate="''"
               value="{{$originalPoem->year ?? $translatedPoem->year ?? ''}}"
               data-vv-as="{{ trans('admin.poem.columns.year') }}"
               @input="validate($event)" class="form-control"
               :class="{'form-control-danger': errors.has('year'), 'form-control-success': fields.year && fields.year.valid}"
               id="year" name="year" placeholder="@lang('admin.poem.columns.year')"
                style="flex-grow: 1; display: inline-block;">
        <input type="text" v-model="form.month"
               v-validate="''"
               value="{{$originalPoem->month ?? $translatedPoem->month ?? ''}}"
               data-vv-as="{{ trans('admin.poem.columns.month') }}"
               @input="validate($event)" class="form-control"
               :class="{'form-control-danger': errors.has('month'), 'form-control-success': fields.month && fields.month.valid}"
               id="month" name="month" placeholder="@lang('admin.poem.columns.month')"
                style="flex-grow: 1; display: inline-block;">
        <input type="text" v-model="form.date"
               v-validate="''"
               value="{{$originalPoem->date ?? $translatedPoem->date ?? ''}}"
               data-vv-as="{{ trans('admin.poem.columns.date') }}"
               @input="validate($event)" class="form-control"
               :class="{'form-control-danger': errors.has('date'), 'form-control-success': fields.date && fields.date.valid}"
               id="date" name="date" placeholder="@lang('admin.poem.columns.date')"
                style="flex-grow: 1; display: inline-block;">
        <div v-if="errors.has('year') || errors.has('month') || errors.has('date')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('year') }}</div>
    </div>
</div>


@if(Auth::user()->is_admin)
<div class="form-group row"
     :class="{'has-danger': errors.has('bedtime_post_id') }">
    <label for="bedtime_post_id" class="col-form-label text-md-right"
           :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.bedtime_post_id') }}</label>
    <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.bedtime_post_id"
               v-validate="''"
               value="{{$originalPoem->bedtime_post_id ?? $translatedPoem->bedtime_post_id ?? ''}}"
               data-vv-as="{{ trans('admin.poem.columns.bedtime_post_id') }}"
               @input="validate($event)" class="form-control"
               :class="{'form-control-danger': errors.has('bedtime_post_id'), 'form-control-success': fields.bedtime_post_id && fields.bedtime_post_id.valid}"
               id="bedtime_post_id" name="bedtime_post_id" placeholder="">
        <div v-if="errors.has('bedtime_post_id')" class="form-control-feedback form-text" v-cloak>@{{
            errors.first('bedtime_post_id') }}
        </div>
    </div>
</div>

<div class="form-group row"
     :class="{'has-danger': errors.has('bedtime_post_title')}">
    <label for="bedtime_post_title" class="col-form-label text-md-right"
           :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.bedtime_post_title') }}</label>
    <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.bedtime_post_title"
               v-validate="''"
               value="{{$originalPoem->bedtime_post_title ?? $translatedPoem->bedtime_post_title ?? ''}}"
               data-vv-as="{{ trans('admin.poem.columns.bedtime_post_title') }}"
               @input="validate($event)"
               class="form-control"
               :class="{'form-control-danger': errors.has('bedtime_post_title'), 'form-control-success': fields.bedtime_post_title && fields.bedtime_post_title.valid}"
               id="bedtime_post_title" name="bedtime_post_title" placeholder="">
        <div v-if="errors.has('bedtime_post_title')" class="form-control-feedback form-text" v-cloak>@{{
            errors.first('bedtime_post_title') }}
        </div>
    </div>
</div>
@endif

<div class="form-group row hidden"
     :class="{'has-danger': errors.has('length'), 'has-success': fields.length && fields.length.valid }">
    <label for="length" class="col-form-label text-md-right"
           :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.length') }}</label>
    <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.length"
               v-validate="''"
               data-vv-as="{{ trans('admin.poem.columns.length') }}"
               @input="validate($event)" class="form-control"
               :class="{'form-control-danger': errors.has('length'), 'form-control-success': fields.length && fields.length.valid}"
               id="length" name="length" placeholder="">
        <div v-if="errors.has('length')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('length') }}
        </div>
    </div>
</div>


<div class="form-check row hidden">
    <div class="ml-md-auto" :class="isFormLocalized ? 'col-md-8' : 'col-md-10'">
        <input class="form-check-input" id="need_confirm" type="checkbox"
               v-model="form.need_confirm"
               v-validate="''"
               data-vv-as="{{ trans('admin.poem.columns.need_confirm') }}"
               data-vv-name="need_confirm" name="need_confirm_fake_element">
        <label class="form-check-label" for="need_confirm">
            {{ trans('admin.poem.columns.need_confirm') }}
        </label>
        <input type="hidden" name="need_confirm" :value="0">
    </div>
</div>
