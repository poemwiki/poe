
@if(isset($originalPoem))
    <div class="form-check row">
        <label class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">
            {{ trans('poem.original work') }}
        </label>
        《<a href="{{$originalPoem->url}}">{{ $originalPoem->title }}</a>》
        <input type="hidden" name="original_id"  v-model="form.original_id">
    </div>
@endif

    <input type="hidden" name="translated_id" v-model="form.translated_id">

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
     :class="{'has-danger': errors.has('preface') }">
    <label for="preface" class="col-form-label text-md-right"
           :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.preface') }}</label>
    <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <textarea type="text" v-model="form.preface"
               v-validate="'max:300'"
               data-vv-as="{{ trans('admin.poem.columns.preface') }}"
               @input="validate($event)" class="form-control"
               :class="{'form-control-danger': errors.has('preface'), 'form-control-success': fields.preface && fields.preface.valid}"
               id="preface" name="preface" placeholder=""></textarea>
        <div v-if="errors.has('preface')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('preface') }}
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

{{--poet_id--}}
<div class="form-group row"
     :class="{'has-danger': errors.has('poet_id'), 'has-success': fields.poet_id && fields.poet_id.valid }">
  <label for="poet_id" class="col-form-label text-md-right required"
         :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.poet_id') }}</label>
  <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">

    <input type="hidden" name="poet" v-model="form.poet">
    <v-select :options="authorList" label="label" :reduce="label => label.id"
              taggable :create-option="label => ({
                label: label,
                id: 'new_' + label,
                source: '',
                label_en: label,
                label_cn: label,
                url: '',
                avatar_url: '/images/avatar-default.png'
               })"
              :disabled="(form.translated_id || form.original_id) > 0"
              @option:selected="onSelectPoet"
              @search="onSearchPoet"
              @search:focus="onSearchPoetFocus"

              ref="poet"
              v-model="form.poet_id"
              :class="{'form-control-danger': errors.has('poet_id'), 'form-control-success': fields.poet_id && fields.poet_id.valid, 'poet-selector': true}"
              class="relative"
              v-validate="'required'"
              data-vv-as="{{ trans('admin.poem.columns.poet_id') }}" data-vv-name="poet_id"
              name="poet_id_fake_element"
    >
      <template slot="option" slot-scope="option">
        <div :title="option.source ? '链接到作者页' : 'PoemWiki 暂无该作者，将链接到搜索页'" class="author-option">
          <span :class="option.source ? 'poemwiki-link' : ''">@{{ option.label }}</span>
          <img class="author-option-avatar" :src="option.avatar_url" :alt="option.label">
          <span :class="'author-option-source ' + option.source" class="absolute text-xs leading-loose right-0 bg-white inline-block text-right text-gray-400">@{{option.source || '暂无该作者'}}</span>
        </div>
      </template>

      <template slot="selected-option" slot-scope="option">
{{--    <a href="author/new or author page url" target="_blank"></a>--}}
        <span :class="option.source ? 'poemwiki-link' : ''">@{{option.label}}</span>
      </template>
    </v-select>

    <input type="hidden" name="poet_id" :value="form.poet_id">

    <div v-if="errors.has('poet_id')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('poet_id') }}</div>
  </div>
</div>

{{--poet_cn TODO poet_cn should be poet_name_translated (the translated name coresspond to current language_id) --}}
<div class="form-group row"
     :class="{'hidden' : _.isNumber(form.poet_id), 'has-danger': errors.has('poet_cn') }">
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

{{--is_original--}}
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

{{--genre_id--}}
<div class="form-group row"
     :class="{'hidden' : form.is_original==0, 'has-danger': errors.has('genre_id'), 'has-success': fields.genre_id && fields.genre_id.valid }">
    <label for="genre_id" class="col-form-label text-md-right"
           :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.genre_id') }}</label>

    <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <select class="form-control"
                :class="{'form-control-danger': errors.has('genre_id'), 'form-control-success': fields.genre_id && fields.genre_id.valid}"
                id="genre_id" v-model="form.genre_id"
                v-validate="''"
                data-vv-as="{{ trans('admin.poem.columns.genre_id') }}" data-vv-name="genre_id"
                name="genre_id_fake_element">
            <option value="" :selected="form.genre_id==''">  </option>
            @foreach($genreList as $genre)
                <option value="{{$genre->id}}" :selected="form.genre_id=={{$genre->id}}">{{ $genre->name_lang }}</option>
            @endforeach
        </select>
        <input type="hidden" name="genre_id" :value="form.genre_id">
        <div v-if="errors.has('genre_id')" class="form-control-feedback form-text" v-cloak>@{{
            errors.first('genre_id') }}
        </div>
    </div>
</div>

{{--translator_id--}}
<div class="form-group row"
     :class="{'hidden' : form.is_original==1,'has-danger': errors.has('translator_id') }">
  <label for="translator_id" class="col-form-label text-md-right"
         :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.translator_id') }}</label>
  <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">


    <input type="hidden" name="translator" :value="form.translator">
    <v-select :options="translatorList" label="label" :reduce="label => label.id"
              taggable :create-option="label => ({
                label: label,
                id: 'new_' + label,
                label_en: label,
                label_cn: label,
                url: ''
               })"
              @option:selected="onSelectTranslator"
              @search="onSearchTranslator"
              @search:focus="onSearchTranslatorFocus"

              ref="translator"
              id="translator_id"
              v-model="form.translator_id"
              :class="{'form-control-danger': errors.has('translator_id'), 'form-control-success': fields.translator_id && fields.translator_id.valid}"
              class="relative"
              v-validate="''"
              data-vv-as="{{ trans('admin.poem.columns.translator_id') }}" data-vv-name="translator_id"
              name="translator_id_fake_element"
    >

      <template slot="option" slot-scope="option">
        <div :title="option.source ? '链接到作者页' : 'PoemWiki 暂无该作者，将链接到搜索页'" class="author-option">
          <span :class="option.source ? 'poemwiki-link' : ''">@{{ option.label }}</span>
          <img class="author-option-avatar" :src="option.avatar_url" :alt="option.label">
          <span :class="'author-option-source ' + option.source" class="absolute text-xs leading-loose right-0 bg-white inline-block text-right text-gray-400">@{{option.source || '暂无该作者'}}</span>
        </div>
      </template>


      <template slot="selected-option" slot-scope="option">
        {{--    <a href="author/new or author page url" target="_blank"></a>--}}
        <span :class="option.source ? 'poemwiki-link' : ''">@{{option.label}}</span>
      </template>
    </v-select>

    <input type="hidden" name="translator_id" :value="form.translator_id">

    <div v-if="errors.has('translator_id')" class="form-control-feedback form-text" v-cloak>@{{
      errors.first('translator_id') }}
    </div>
  </div>
</div>

<div class="form-group row"
     :class="{'has-danger': errors.has('language_id'), 'has-success': fields.language_id && fields.language_id.valid }">
    <label for="language_id" class="col-form-label text-md-right required"
           :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.language_id') }}</label>

    <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <select class="form-control"
                :class="{'form-control-danger': errors.has('language_id'), 'form-control-success': fields.language_id && fields.language_id.valid}"
                id="language_id" v-model="form.language_id"
                v-validate="'required'"
                data-vv-as="{{ trans('admin.poem.columns.language_id') }}" data-vv-name="language_id"
                name="language_id_fake_element">
            @foreach($languageList as $lang)
                <option value="{{$lang->id}}" :selected="form.language_id=={{$lang->id}}">{{ $lang->name_lang }} ({{ $lang->name }})</option>
            @endforeach
        </select>
        <input type="hidden" name="language_id" :value="form.language_id">
        <div v-if="errors.has('language_id')" class="form-control-feedback form-text" v-cloak>@{{
            errors.first('language_id') }}
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

<div class="form-group row"
     :class="{'has-danger': errors.has('location') }">
    <label for="location" class="col-form-label text-md-right"
           :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.poem.columns.location') }}</label>
    <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.location"
               v-validate="''"
               value="{{$originalPoem->location ?? $translatedPoem->location ?? ''}}"
               data-vv-as="{{ trans('admin.poem.columns.location') }}"
               @input="validate($event)" class="form-control"
               :class="{'form-control-danger': errors.has('location'), 'form-control-success': fields.location && fields.location.valid}"
               id="location" name="location" placeholder="">
        <div v-if="errors.has('location')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('location') }}</div>
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
