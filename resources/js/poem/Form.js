import BaseForm from '../components/BaseForm';
import { codemirror } from 'vue-codemirror';// import language js

import vSelect from 'vue-select';
vSelect.props.reduce.default = function (option) {
  return option.id;
};

// import base style
import 'codemirror/lib/codemirror.css'

Vue.component('poem-form', {
  components: {
    codemirror, vSelect
  },
  mixins: [BaseForm],
  props: ['defaultAuthors'],
  data: function () {
    return {
      form: {
        title: '',
        language_id: 1,
        is_original: 1,
        poet: '',
        bedtime_post_id:  '' ,
        bedtime_post_title:  '' ,
        poem: "\n\n\n\n\n",
        length: '',
        translator: '',
        from:  '' ,
        year:  '' ,
        // month:  '' ,
        // date:  '' ,
        location:  '' ,
        // need_confirm:  false ,
        // is_lock:  false ,
        // content_id:  '' ,
        original_id: '',
        translated_id: '',
        genre_id: '',
        poet_id: null,
        translator_id: null,
        poet_wikidata_id: null,
        translator_wikidata_id: null,
      },

      authorList: this.defaultAuthors,
      newAuthor: null,
      translatorList: _.clone(this.defaultAuthors),
      newTranslator: null,
      cmOptions: {
        tabSize: 2,
        mode: 'text/plain',
        lineNumbers: true,
        line: true,
        // more CodeMirror options...
      }
    }
  },

  watch: {
  },

  mounted: function() {
    if(!this.form.poet_id && this.form.poet) {
      this.newAuthor = {
        id: 'new_' + this.form.poet,
        label: (this.form.poet_cn && this.form.poet_cn!==this.form.poet) ? this.form.poet+'（'+this.form.poet_cn+'）' : this.form.poet,
        label_en: this.form.poet,
        label_cn: this.form.poet_cn,
        url: ''
      };
      this.authorList.push(this.newAuthor);
      this.form.poet_id = 'new_' + this.form.poet;
    }
    if(!this.form.translator_id && this.form.translator) {
      this.newTranslator = {
        id: 'new_' + this.form.translator,
        label: this.form.translator,
        label_en: this.form.translator,
        label_cn: this.form.translator,
        url: ''
      };
      this.translatorList.push(this.newTranslator);
      this.form.translator_id = 'new_' + this.form.translator;
    }
  },

  methods: {
    isNew: function(id) {
      return _.startsWith(id, 'new_');
    },

    getPostData: function getPostData() {
      let data = _.clone(this.form);
      if(_.startsWith(data.poet_id, 'Q')) {
        data.poet_wikidata_id = this.form.poet_id.replace('Q', '');
        data.poet_id = null;
      }
      if(_.startsWith(data.translator_id, 'Q')) {
        data.translator_wikidata_id = this.form.translator_id.replace('Q', '');
        data.translator_id = null;
      }

      if(this.isNew(data.poet_id)) {
        data.poet_id = 'new';
        data.poet_wikidata_id = null; // you need to set wikidata_id null here because initial wikidata_id may not null
      }
      if(this.isNew(data.translator_id)) {
        data.translator_id = 'new';
        data.translator_wikidata_id = null;
      }
      return data;
    },

    onSelectPoet: function(option) {
      this.form.poet = option.label_en;
      this.form.poet_cn = option.label_cn;
      if(this.isNew(this.form.poet_id)) {
        this.form.poet_wikidata_id = null;
      }
      console.log('selected poet', option, this.form.poet, this.form.poet_cn, this.form.poet_id);
    },
    onSelectTranslator: function(option) {
      this.form.translator = option.label_en;
      if(this.isNew(this.form.translator_id)) {
        this.form.translator_wikidata_id = null;
      }
      console.log('selected translator', option, this.form.translator, this.form.translator_id);
    },

    onSearchPoetFocus: function(query, loading) {
      console.log('poet input focus');
      loading = loading || this.$refs.poet.toggleLoading;
      if(this.isNew(this.form.poet_id) && query === undefined) {
        loading(true);
        this.searchAuthor('poet_id', loading, this.form.poet, this);
      }
    },
    onSearchTranslatorFocus: function(query, loading) {
      console.log('translator input focus');
      loading = loading || this.$refs.translator.toggleLoading;
      if(this.isNew(this.form.translator_id) && query === undefined) {
        loading(true);
        this.searchTranslator('translator_id', loading, this.form.translator, this);
      }
    },

    onSearchPoet: function(keyword, loading) {
      if(keyword.length) {
        this.form.poet = keyword;
        loading(true);
        this.searchAuthor('poet_id', loading, keyword, this);
      } else {
        this.searchAuthor('poet_id', loading, this.form.poet, this);
      }
    },
    onSearchTranslator: function(keyword, loading) {
      if(keyword.length) {
        this.form.translator = keyword;
        loading(true);
        this.searchTranslator('translator_id', loading, keyword, this);
      }
    },

    searchAuthor: _.debounce((field, loading, search, vm) => {
      console.log('searching');
      axios(
        `/q/author/${encodeURI(search)}/${vm.form[field]}`
      ).then(res => {
        if(res?.data?.length) {
          vm.authorList = res.data;
          if(vm.isNew(vm.form.poet_id)) {
            vm.authorList.push(vm.newAuthor);
          }
        }

        console.log('search result: ', res?.data?.length, _.map(vm.authorList, 'id'), _.map(vm.authorList, 'label'));
        loading(false);
      });
    }, 450),
    searchTranslator: _.debounce((field, loading, search, vm) => {
      axios(
        `/q/author/${encodeURI(search)}/${vm.form[field]}`
      ).then(res => {
        if(res?.data?.length) {
          vm.translatorList = res.data;
          if(vm.isNew(vm.form.translator_id)) {
            vm.translatorList.push(vm.newTranslator);
          }
        }

        console.log(res?.data?.length, _.map(vm.translatorList, 'id'), _.map(vm.translatorList, 'label'));
        loading(false);
      });
    }, 450),

    onSuccess: function onSuccess(data) {
      if (data?.code === 0) {
        this.$notify({
          type: 'success',
          title: '操作成功',
          text: '您的修改已提交。' + (data.redirect ? '正在跳转到诗歌页面...' : '')
        });
        if(data.redirect){
          setTimeout(() => {
            this.submiting = false;
            location.href = data.redirect;
          }, 2000);
          return;
        }
      }
      this.submiting = false;
    },

    onCmInput(newContent) {
      this.form.poem = newContent;
    },
    onCmCodeChange() {
      console.log('content: ', this.form.poem);
      this.$validator.validate('poem', this.form.poem);
    }
  },
  computed: {
    codemirror() {
      return this.$refs.cmEditor.codemirror;
    }
  },

});
