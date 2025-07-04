@if(isset($originalPoem) && $mode==='create translated')
    <div class="form-check">
        <label class="col-form-label text-md-right">
            {{ trans('admin.poem.columns.original_id') }}
        </label>
        <a href="{{$originalPoem->url}}">{{ $originalPoem->title }}</a>
        <input type="hidden" name="original_id"  v-model="form.original_id">
    </div>
@endif


<input type="hidden" name="translated_id" v-model="form['#translated_id']">


@if($mode==='create new' || $mode==='create original')
<div>
  <fieldset class="radio_group">

    <legend>@lang('Claim Authorship')</legend>

    <label for="owner_type_none"><input type="radio" id="owner_type_none"
       v-model="form.is_owner_uploaded" name="is_owner_uploaded_fake_element" :value="0" />@choice('Authorship', 0)&nbsp;&nbsp;(仅管理员可删除)</label>

    @if(empty($originalPoem))
      <label for="owner_type_uploader"><input type="radio" id="owner_type_uploader"
          v-model="form.is_owner_uploaded" name="is_owner_uploaded_fake_element" :value="1" />@choice('Authorship', 1)</label>
    @endif

    @if(empty($translatedPoem))
{{--    <label for="owner_type_translatorUploader"><input type="radio" id="owner_type_translatorUploader"
       v-model="form.is_owner_uploaded" name="is_owner_uploaded_fake_element" :value="2" />@choice('Authorship', 2)</label>--}}
    @endif


    <input type="hidden" name="is_owner_uploaded" :value="form.is_owner_uploaded">
  </fieldset>
</div>
@endif

<div :class="{'has-danger': errors.has('title'), 'has-success': fields.title && fields.title.valid }">
<label for="title" class="col-form-label text-md-right required">{{ trans('admin.poem.columns.title') }}</label>
<div>
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


<div :class="{'has-danger': errors.has('subtitle') }">
<label for="subtitle" class="col-form-label text-md-right">{{ trans('admin.poem.columns.subtitle') }}</label>
<div>
<input type="text" v-model="form.subtitle"
       v-validate="'max:128'"
       data-vv-as="{{ trans('admin.poem.columns.subtitle') }}"
       @input="validate($event)" class="form-control"
       :class="{'form-control-danger': errors.has('subtitle'), 'form-control-success': fields.subtitle && fields.subtitle.valid}"
       id="subtitle" name="subtitle" placeholder="">
<div v-if="errors.has('subtitle')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('subtitle') }}
</div>
</div>
</div>

<div :class="{'has-danger': errors.has('preface') }">
<label for="preface" class="col-form-label text-md-right">{{ trans('admin.poem.columns.preface') }}</label>
<div>
    <textarea type="text" v-model="form.preface"
           v-validate="'max:10000'"
           data-vv-as="{{ trans('admin.poem.columns.preface') }}"
           @input="validate($event)" class="form-control"
           :class="{'form-control-danger': errors.has('preface'), 'form-control-success': fields.preface && fields.preface.valid}"
           id="preface" name="preface" placeholder=""></textarea>
    <div v-if="errors.has('preface')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('preface') }}
    </div>
</div>
</div>

<div :class="{'has-danger': errors.has('poem'), 'has-success': fields.poem && fields.poem.valid }">
<label for="poem" class="col-form-label text-md-right required">{{ trans('admin.poem.columns.poem') }}</label>
<div>
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
            v-on:before-change="onCmBeforeChange"
            @blur="onCmCodeBlur"
        ></codemirror>
    </div>
    <div v-if="errors.has('poem')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('poem') }}</div>
</div>
</div>


<div class="flex space-x-6" :class="{'has-danger': errors.has('year') }">

<div class="w-2/3 flex space-x-2"
   title="{{ trans('admin.poem.columns.time') }}">

<input type="text" v-model="form.year"
       v-validate="''"
       value="{{$originalPoem->year ?? $translatedPoem->year ?? ''}}"
       data-vv-as="{{ trans('admin.poem.columns.year') }}"
       @input="validate($event)"
       :class="{'form-control-danger': errors.has('year'), 'form-control-success': fields.year && fields.year.valid}"
       id="year" name="year" placeholder="@lang('admin.poem.columns.year')"
       style="flex-grow: 1; display: inline-block;">
<input type="text" v-model="form.month"
       v-validate="''"
       value="{{$originalPoem->month ?? $translatedPoem->month ?? ''}}"
       data-vv-as="{{ trans('admin.poem.columns.month') }}"
       @input="validate($event)"
       :class="{'form-control-danger': errors.has('month'), 'form-control-success': fields.month && fields.month.valid}"
       id="month" name="month" placeholder="@lang('admin.poem.columns.month')"
       style="flex-grow: 1; display: inline-block;">
<input type="text" v-model="form.date"
       v-validate="''"
       value="{{$originalPoem->date ?? $translatedPoem->date ?? ''}}"
       data-vv-as="{{ trans('admin.poem.columns.date') }}"
       @input="validate($event)"
       :class="{'form-control-danger': errors.has('date'), 'form-control-success': fields.date && fields.date.valid}"
       id="date" name="date" placeholder="@lang('admin.poem.columns.date')"
       style="flex-grow: 1; display: inline-block;">
<div v-if="errors.has('year') || errors.has('month') || errors.has('date')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('year') }}</div>

</div>

<div class="w-1/3">

<div class="{'has-danger': errors.has('location') }">

    <input type="text"
           placeholder="{{ trans('admin.poem.columns.location') }}"
           v-model="form.location"
           v-validate="''"
           value="{{$originalPoem->location ?? $translatedPoem->location ?? ''}}"
           data-vv-as="{{ trans('admin.poem.columns.location') }}"
           @input="validate($event)" class="form-control"
           :class="{'form-control-danger': errors.has('location'), 'form-control-success': fields.location && fields.location.valid}"
           id="location" name="location" placeholder="">
    <div v-if="errors.has('location')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('location') }}</div>

</div>

</div>

</div>


{{--poet_id--}}
<div :class="{'has-danger': errors.has('poet_id'), 'has-success': fields.poet_id && fields.poet_id.valid }">
<label for="poet_id" class="col-form-label text-md-right required">{{ trans('admin.poem.columns.poet_id') }}</label>
<div>

<input type="hidden" name="poet" v-model="form.poet">
<v-select :options="authorList" label="label" :reduce="label => label.id"
          taggable
          :filterable="false"
          :create-option="label => ({
            label: label,
            id: 'new_' + label,
            source: '',
            label_en: label,
            label_cn: label,
            url: '',
            avatar_url: '/images/avatar-default.png'
           })"
          :disabled="form.translated_id > 0 || form.is_owner_uploaded == 1"
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
      <span class="author-option-label" :class="option.source ? 'poemwiki-link' : ''">@{{ option.label }}</span>
      <span class="author-option-desc">@{{ option.desc }}</span>
      <img class="author-option-avatar" :src="option.avatar_url" :alt="option.label">
      <span :class="'author-option-source ' + option.source" class="absolute text-xs leading-loose right-0 bg-white inline-block text-right text-gray-400">@{{option.source || '仅录入名字，暂不关联作者'}}</span>
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
<div :class="{'hidden' : _.isNumber(form.poet_id) || form.is_owner_uploaded > 0, 'has-danger': errors.has('poet_cn') }">
<label for="poet_cn" class="col-form-label text-md-right">{{ trans('admin.poem.columns.poet_cn') }}</label>
<div>
<input type="text" v-model="form.poet_cn"
       value="{{$originalPoem->poet_cn ?? $translatedPoem->poet_cn ?? ''}}"
       v-validate="''"
       data-vv-as="{{ trans('admin.poem.columns.poet_cn') }}"
       @input="validate($event)" @blur="validate($event)"
       class="form-control"
       :class="{'form-control-danger': errors.has('poet_cn'), 'form-control-success': fields.poet_cn && fields.poet_cn.valid}"
       id="poet_cn" name="poet_cn" placeholder="">
<div v-if="errors.has('poet_cn')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('poet_cn') }}
</div>
</div>
</div>

{{--is_original--}}
<div :class="{'has-danger': errors.has('is_original'), 'has-success': fields.is_original && fields.is_original.valid }">
<label for="is_original" class="col-form-label text-md-right required">{{ trans('admin.poem.columns.is_original') }}</label>
<div>
    <select class="form-control"
            :class="{'form-control-danger': errors.has('is_original'), 'form-control-success': fields.is_original && fields.is_original.valid}"
            id="is_original"
            v-model="form.is_original"
            v-validate="'required'"
            value="{{!empty($originalPoem) ? 0 : (!empty($translatedPoem) ? 1 : '')}}"
            @if(!empty($originalPoem) || !empty($translatedPoem))
              disabled
            @else
              :disabled="form.is_owner_uploaded > 0"
            @endif
            data-vv-as="{{ trans('admin.poem.columns.is_original') }}" data-vv-name="is_original"
            name="is_original_fake_element">
        <option :value="1"
                :selected="form.is_original===1">{{ trans('admin.poem.is_original_enum.original') }}</option>
        <option :value="0"
                :selected="form.is_original===0">{{ trans('admin.poem.is_original_enum.translated') }}</option>
    </select>
    <input type="hidden" name="is_original" :value="form.is_original">
    <div v-if="errors.has('is_original')" class="form-control-feedback form-text" v-cloak>@{{
        errors.first('is_original') }}
    </div>
</div>
</div>



@if($mode === 'edit')

  {{-- original link --}}
  <div :class="{'hidden':form.is_original===1}">
    <label for="poet_cn" class="col-form-label">@lang('Translated From Poem Link')</label>
    <div>
      <input type="text" v-model="form.original_link"
             autocomplete="off"
             v-validate="'regex:^https?://{{request()->getHost()}}/p/[a-zA-Z0-9]+'"
             data-vv-as="@lang('Translated From Poem Link')"
             data-vv-name="original_link"
             @input="validate($event)" @blur="validate($event)"
             class="form-control"
             :class="{'form-control-danger': errors.has('original_link'), 'form-control-success': fields.original_link && fields.original_link.valid}"
             id="original_link" name="original_link" placeholder="">
      <div v-if="errors.has('original_link')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('original_link') }}
      </div>
    </div>
  </div>
@endif

{{--translator_id--}}
<div :class="{'hidden' : form.is_original==1,'has-danger': errors.has('translator_id')}">
  <label for="translator_id" class="col-form-label text-md-right" :class="{'required': form.is_original==0}">{{ trans('admin.poem.columns.translator_id') }}</label>
  <div>
    <input type="hidden" name="translator" :value="form.translator">
    <v-select :options="translatorList" label="label" :reduce="label => label.id"
              taggable :create-option="label => ({
                label: label,
                id: 'new_' + label,
                label_en: label,
                label_cn: label,
                url: '',
                avatar_url: '/images/avatar-default.png'
               })"
              :multiple="true"
              :push-tags="true"
              @option:selected="onSelectTranslator"
              @option:deselected="onDeselectTranslator"
              @search="onSearchTranslator"
              @search:focus="onSearchTranslatorFocus"
              ref="translator"
              id="translator_ids"
              v-model="form.translator_ids"
              :class="{'form-control-danger': errors.has('translator_ids'), 'form-control-success': fields.translator_ids && fields.translator_ids.valid}"
              class="relative"
              v-validate="form.is_original==0 ? 'required' : ''"
              data-vv-as="{{ trans('admin.poem.columns.translator_ids') }}" data-vv-name="translator_ids"
              name="translator_ids_fake_element"
    >

      <template slot="option" slot-scope="option">
        <div :title="option.source ? '链接到作者页' : 'PoemWiki 暂无该作者，将链接到搜索页'" class="author-option">
          <span class="author-option-label" :class="option.source ? 'poemwiki-link' : ''">@{{ option.label }}</span>
          <span class="author-option-desc">@{{ option.desc }}</span>
          <img class="author-option-avatar" :src="option.avatar_url" :alt="option.label">
          <span :class="'author-option-source ' + option.source" class="absolute text-xs leading-loose right-0 bg-white inline-block text-right text-gray-400">@{{option.source || '仅录入名字，暂不关联作者'}}</span>
        </div>
      </template>

      <template slot="selected-option" slot-scope="option">
        {{--    <a href="author/new or author page url" target="_blank"></a>--}}
        <span :class="option.source ? 'poemwiki-link' : ''">@{{option.label}}</span>
      </template>
    </v-select>

    <input type="hidden" name="translator_ids" :value="form.translator_ids">

    <div v-if="errors.has('translator_ids')" class="form-control-feedback form-text" v-cloak>@{{
      errors.first('translator_ids') }}
    </div>
  </div>
</div>

<div :class="{'has-danger': errors.has('language_id'), 'has-success': fields.language_id && fields.language_id.valid }">
  <label for="language_id" class="col-form-label text-md-right required">{{ trans('admin.poem.columns.language_id') }}</label>

  <div>
      <select class="form-control"
              :class="{'form-control-danger': errors.has('language_id'), 'form-control-success': fields.language_id && fields.language_id.valid}"
              id="language_id" v-model="form.language_id"
              v-validate="'required'"
              data-vv-as="{{ trans('admin.poem.columns.language_id') }}" data-vv-name="language_id"
              name="language_id_fake_element">
          @foreach($languageList as $lang)
              <option :value="{{$lang->id}}" :selected="form.language_id=={{$lang->id}}">{{ $lang->name_lang }} ({{ $lang->name }})</option>
          @endforeach
      </select>
      <input type="hidden" name="language_id" :value="form.language_id">
      <div v-if="errors.has('language_id')" class="form-control-feedback form-text" v-cloak>@{{
          errors.first('language_id') }}
      </div>
  </div>
</div>

<div :class="{'has-danger': errors.has('from') }">
<label for="from" class="col-form-label text-md-right">{{ trans('admin.poem.columns.from') }}</label>
<div>
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

{{--genre_id--}}
<!--
<div :class="{'hidden' : form.is_original==0, 'has-danger': errors.has('genre_id'), 'has-success': fields.genre_id && fields.genre_id.valid }">
  <label for="genre_id" class="col-form-label text-md-right">{{ trans('admin.poem.columns.genre_id') }}</label>

  <div>
    <select class="form-control"
            :class="{'form-control-danger': errors.has('genre_id'), 'form-control-success': fields.genre_id && fields.genre_id.valid}"
            id="genre_id" v-model="form.genre_id"
            v-validate="''"
            data-vv-as="{{ trans('admin.poem.columns.genre_id') }}" data-vv-name="genre_id"
            name="genre_id_fake_element">
      <option value="" :selected="form.genre_id==''">  </option>
{{--      @foreach($genreList as $genre)--}}
{{--        <option value="{{$genre->id}}" :selected="form.genre_id=={{$genre->id}}">{{ $genre->name_lang }}</option>--}}
{{--      @endforeach--}}
    </select>
    <input type="hidden" name="genre_id" :value="form.genre_id">
    <div v-if="errors.has('genre_id')" class="form-control-feedback form-text" v-cloak>@{{
      errors.first('genre_id') }}
    </div>
  </div>
</div>
-->


<div class="hidden"
 :class="{'has-danger': errors.has('length'), 'has-success': fields.length && fields.length.valid }">
  <label for="length" class="col-form-label text-md-right">{{ trans('admin.poem.columns.length') }}</label>
  <div>
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


<div class="form-check hidden">
<div class="ml-md-auto">
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

<div class="form-check" style="margin-top: 2em;">
  <div class="ml-md-auto">
    <label class="form-check-label" for="agree">
      <input class="form-check-input" id="agree" type="checkbox"
             v-model="form.agree"
             v-validate="'required:true'"
             data-vv-as="@lang('Agree Convention')"
             data-vv-name="agree" name="agree_fake_element">
      @lang('Agree')
    </label>
    <div v-if="errors.has('agree')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('agree') }}
    </div>
  </div>
</div>
