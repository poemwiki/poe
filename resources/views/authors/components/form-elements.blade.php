@php
  $langs = \App\Repositories\LanguageRepository::allInUse();

  $localeCol = $langs->filter(function ($item) use($locales) {
    return in_array($item->locale, $locales->toArray());
  })->pluck('name_lang', 'locale');

/** @var \App\Models\Author $author */

@endphp

<div class="row">
  @foreach($locales as $locale)
    <div class="col-md" v-show="shouldShowLangGroup('{{ $locale }}')" v-cloak>
      <div class="form-group row align-items-center"
           :class="{'has-danger': errors.has('name_lang_{{ $locale }}'), 'has-success': fields['name_lang_{{ $locale }}'] && fields['name_lang_{{ $locale }}'].valid }">
        <label for="name_lang_{{ $locale }}"
               class="col-md-2 col-form-label text-md-right required">{{ trans('admin.author.columns.name_lang') }}
          ({{$localeCol->get($locale)}})</label>
        <div>
          <input type="text"
                 v-model="form.name_lang['{{ $locale }}']"
                 value="{{$author->getTranslated('name_lang', $locale)}}"
                 v-validate="'required'" @input="validate($event)"
                 class=""
                 :class="{'form-control-danger': errors.has('name_lang_{{ $locale }}'), 'form-control-success': fields['name_lang_{{ $locale }}'] && fields['name_lang_{{ $locale }}'].valid }"
                 id="name_lang_{{ $locale }}" name="name_lang_{{ $locale }}"
                 placeholder="{{ trans('admin.author.columns.name_lang') }}">

            <div v-visible="errors.has('name_lang_{{ $locale }}')" class="form-control-feedback form-text" v-cloak>{{'{{'}}
              errors.first('name_lang_{{ $locale }}') }}
            </div>

        </div>
      </div>
    </div>
  @endforeach
</div>

<div class="row">
  @foreach($locales as $locale)
    <div class="col-md" v-show="shouldShowLangGroup('{{ $locale }}')" v-cloak>
      <div class="form-group row align-items-center"
           :class="{'has-danger': errors.has('describe_lang_{{ $locale }}'), 'has-success': fields['describe_lang_{{ $locale }}'] && fields['describe_lang_{{ $locale }}'].valid }">
        <label for="describe_lang_{{ $locale }}"
               class="col-md-2 col-form-label text-md-right">{{ trans('admin.author.columns.describe_lang') }}
          ({{$localeCol->get($locale)}})</label>
        <div>
          <textarea type="text"
                    v-model="form.describe_lang['{{ $locale }}']"
                    value="{{$author->getTranslated('describe_lang', $locale)}}"
                    v-validate="''" @input="validate($event)"
                    class=""
                    :class="{'form-control-danger': errors.has('describe_lang_{{ $locale }}'), 'form-control-success': fields['describe_lang_{{ $locale }}'] && fields['describe_lang_{{ $locale }}'].valid }"
                    rows="6"
                    id="describe_lang_{{ $locale }}" name="describe_lang_{{ $locale }}"
                    placeholder="{{ trans('admin.author.columns.describe_lang') }}"></textarea>
          <div class="form-control-feedback form-text" v-cloak>{{'{{'}}
            errors.first('describe_lang_{{ $locale }}') }}
          </div>
        </div>
      </div>
    </div>
  @endforeach
</div>


<div class="form-group row"
     :class="{'has-danger': errors.has('nation_id'), 'has-success': fields.nation_id && fields.nation_id.valid }">
  <label for="nation_id" class="col-form-label text-md-right"
         :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.author.columns.nation_id') }}</label>

  <div>
    <select class=""
            :class="{'form-control-danger': errors.has('nation_id'), 'form-control-success': fields.nation_id && fields.nation_id.valid}"
            id="nation_id" v-model="form.nation_id"
            v-validate="''"
            data-vv-as="{{ trans('admin.author.columns.nation_id') }}" data-vv-name="nation_id"
            name="nation_id_fake_element">
      <option value="" :selected="form.nation_id==''"></option>
      @foreach($nationList as $item)
        <option value="{{$item->id}}" :selected="form.nation_id=={{$item->id}}">{{ $item->name_lang }}</option>
      @endforeach
    </select>
    <input type="hidden" name="nation_id" :value="form.nation_id">
    <div class="form-control-feedback form-text" v-cloak>@{{
      errors.first('nation_id') }}
    </div>
  </div>
</div>


<div class="form-group row"
     :class="{'has-danger': errors.has('dynasty_id'), 'has-success': fields.dynasty_id && fields.dynasty_id.valid }">
  <label for="dynasty_id" class="col-form-label text-md-right"
         :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.author.columns.dynasty_id') }}</label>

  <div>
    <select class=""
            :disabled="form.nation_id!=='1'"
            :class="{'form-control-danger': errors.has('dynasty_id'), 'form-control-success': fields.dynasty_id && fields.dynasty_id.valid}"
            id="dynasty_id" v-model="form.dynasty_id"
            v-validate="''"
            data-vv-as="{{ trans('admin.author.columns.dynasty_id') }}" data-vv-name="dynasty_id"
            name="dynasty_id_fake_element">
      <option value="" :selected="form.dynasty_id==''"></option>
      @foreach($dynastyList as $item)
        <option value="{{$item->id}}" :selected="form.dynasty_id=={{$item->id}}" title="{{$item->describe_lang}}">{{ $item->name_lang }}</option>
        @foreach($item->children as $child)
          <option value="{{$child->id}}" :selected="form.dynasty_id=={{$child->id}}" title="{{$item->describe_lang}}">&emsp;&emsp;{{ $child->name_lang }}</option>
        @endforeach
      @endforeach
    </select>
    <input type="hidden" name="dynasty_id" :value="form.dynasty_id">
    <div class="form-control-feedback form-text" v-cloak>@{{
      errors.first('dynasty_id') }}
    </div>
  </div>
</div>
