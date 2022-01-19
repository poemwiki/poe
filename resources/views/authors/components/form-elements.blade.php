

<div class="row">

  <div class="form-group row align-items-center"
       :class="{'has-danger': errors.has('avatar'), 'has-success': fields['avatar'] && fields['avatar'].valid, 'hidden': !form.id }">
    <label :for="'avatar'"
           class="col-md-2 col-form-label text-md-right required">{{ trans('admin.author.columns.avatar') }}</label>
    <div>
      <div class="wiki-avatar">
        <img :src="form.avatar || form.avatar_url" :alt="form.name_lang[locales[0]]" class="wiki-avatar-img">
        <span class="wiki-avatar-mask wiki-avatar-btn">@{{
          uploadProgress===false ? '@lang('Change Avatar')' : uploadProgress
          }}</span>
        <input type="file" ref="avatar" @change="onAvatarChange" class="wiki-avatar-mask" name="avatar-file" accept="image/*">
      </div>

      <div v-visible="errors.has('avatar')" class="form-control-feedback form-text" v-cloak>@{{errors.first('avatar') }}
      </div>

    </div>
  </div>

</div>

<div class="row">

    <div v-for="locale in locales" :v-key="locale" v-show="shouldShowLangGroup(locale)" v-cloak class="col-md">
      <div class="form-group row align-items-center"
           :class="{'has-danger': errors.has('name_lang_' + locale), 'has-success': fields['name_lang_' + locale] && fields['name_lang_' + locale].valid }">
        <label :for="'name_lang_' + locale"
               class="col-md-2 col-form-label text-md-right required">{{ trans('admin.author.columns.name_lang') }}
          (@{{lang.locales[locale]}})</label>
        <div>
          <input type="text"
                 v-model="form.name_lang[locale]"
                 v-validate="locale == defaultLocale ? 'required' : ''"
                 @input="validate($event)"
                 :class="{
                    'form-control-danger': errors.has('name_lang_' + locale),
                    'form-control-success': fields['name_lang_' + locale] && fields['name_lang_' + locale].valid
                 }"
                 :id="'name_lang_' + locale" :name="'name_lang_' + locale"
                 name="{{ trans('admin.author.columns.name_lang') }}"
                 placeholder="{{ trans('admin.author.columns.name_lang') }}">

            <div v-visible="errors.has('name_lang_' + locale)" class="form-control-feedback form-text" v-cloak>{{'{{'}}
              errors.first('name_lang_' + locale) }}
            </div>

        </div>
      </div>
    </div>

</div>

<div class="row">

    <div v-for="locale in locales" :v-key="locale" v-show="shouldShowLangGroup(locale)" v-cloak class="col-md">
      <div class="form-group row align-items-center"
           :class="{'has-danger': errors.has('describe_lang_' + locale), 'has-success': fields['describe_lang_' + locale] && fields['describe_lang_' + locale].valid }">
        <label :for="'describe_lang_' + locale"
               class="col-md-2 col-form-label text-md-right">{{ trans('admin.author.columns.describe_lang') }}
          (@{{lang.locales[locale]}})</label>
        <div>
          <textarea type="text"
                    v-model="form.describe_lang[locale]"
                    v-validate="''"
                    @input="validate($event)"
                    :class="{
                        'form-control-danger': errors.has('describe_lang_' + locale),
                        'form-control-success': fields['describe_lang_' + locale] && fields['describe_lang_' + locale].valid
                      }"
                    rows="6"
                    :id="'describe_lang_' + locale" :name="'describe_lang_' + locale"
                    placeholder="{{ trans('admin.author.columns.describe_lang') }}"></textarea>
          <div class="form-control-feedback form-text" v-cloak>{{'{{'}}
            errors.first('describe_lang_' + locale) }}
          </div>
        </div>
      </div>
    </div>

</div>


<div class="row">

  <div class="form-group row align-items-center"
       :class="{'has-danger': errors.has('birth'), 'has-success': fields['birth'] && fields['birth'].valid }">
    <label for="birth"
           class="col-md-2 col-form-label text-md-right">{{ trans('admin.author.columns.birth') }}</label>
    <div>
      <input type="date"
             v-model="form.birth"
             v-validate="'date_format:yyyy-MM-dd'"
             @input="validate($event)"
             :class="{
                    'form-control-danger': errors.has('birth'),
                    'form-control-success': fields['birth'] && fields['birth'].valid
                 }"
             :id="birth" name="{{ trans('admin.author.columns.birth') }}"
             placeholder="yyyy-MM-dd">

      <div v-visible="errors.has('birth')" class="form-control-feedback form-text" v-cloak>{{'{{'}}
        errors.first('birth') }}
      </div>

    </div>
  </div>

</div>

<div class="row">

  <div class="form-group row align-items-center"
       :class="{'has-danger': errors.has('death'), 'has-success': fields['death'] && fields['death'].valid }">
    <label for="death"
           class="col-md-2 col-form-label text-md-right">{{ trans('admin.author.columns.death') }}</label>
    <div>
      <input type="date"
             v-model="form.death"
             v-validate="'date_format:yyyy-MM-dd'"
             @input="validate($event)"
             :class="{
                    'form-control-danger': errors.has('death'),
                    'form-control-success': fields['death'] && fields['death'].valid
                 }"
             :id="death" name="{{ trans('admin.author.columns.birth') }}"
             placeholder="yyyy-MM-dd">

      <div v-visible="errors.has('death')" class="form-control-feedback form-text" v-cloak>{{'{{'}}
        errors.first('birth') }}
      </div>

    </div>
  </div>

</div>

<div class="form-group row"
     :class="{'has-danger': errors.has('nation_id'), 'has-success': fields.nation_id && fields.nation_id.valid }">
  <label for="nation_id" class="col-form-label text-md-right"
         :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.author.columns.nation_id') }}</label>

  <div>
    <v-select :options="nationList" label="name_lang" :reduce="name_lang => name_lang.id" :filterable="false"
              @search="onSearchNation"
              v-model="form.nation_id"
              :class="{'form-control-danger': errors.has('nation_id'), 'form-control-success': fields.nation_id && fields.nation_id.valid}"
              v-validate="''"
              data-vv-as="{{ trans('admin.author.columns.nation_id') }}" data-vv-name="nation_id"
              name="nation_id_fake_element"
    ></v-select>

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
    <v-select :options="dynastyList" label="name_lang" :reduce="name_lang => name_lang.id"
              v-model="form.dynasty_id"
              :disabled="form.nation_id!==32"
              :class="{'form-control-danger': errors.has('dynasty_id'), 'form-control-success': fields.dynasty_id && fields.dynasty_id.valid}"
              id="dynasty_id"
              v-validate="''"
              data-vv-as="{{ trans('admin.author.columns.dynasty_id') }}" data-vv-name="dynasty_id"
              name="dynasty_id_fake_element"
    ></v-select>

    <input type="hidden" name="dynasty_id" :value="form.dynasty_id">
    <div class="form-control-feedback form-text" v-cloak>@{{
      errors.first('dynasty_id') }}
    </div>
  </div>
</div>
