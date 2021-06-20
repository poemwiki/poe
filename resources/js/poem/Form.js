import BaseForm from '../components/BaseForm';
import { codemirror } from 'vue-codemirror';// import language js

import vSelect from 'vue-select';
vSelect.props.reduce.default = function (option) {
  return option.id;
};
vSelect.props.filterBy.default = function(option, label, search) {
  return true;//(label || '').toLowerCase().indexOf(search.toLowerCase()) > -1
}

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
        is_owner_uploaded: 0,
        _user_name: '',
        original_link: ''
      },

      authorList: this.defaultAuthors,
      translatorList: _.clone(this.defaultAuthors),
      cmOptions: {
        tabSize: 4,
        mode: 'text/plain',
        lineNumbers: true,
        line: true,
        lineWrapping: true,
        extraKeys: {
          Space: (cm) => {
            var doc = cm.getDoc();
            var cursor = doc.getCursor();

            var pos = {
              line: cursor.line,
              ch: cursor.ch
            }
            var cjkIds = [1, 7, 8, 491];

            if(cjkIds.indexOf(this.form.language_id) === -1) {
              doc.replaceRange(' ', pos);
            } else {
              doc.replaceRange('　', pos);
            }
          },
          // "Shift-Tab": (cm) => cm.execCommand("indentLess"),
        },
        // more CodeMirror options...
      }
    }
  },

  watch: {
    'form.is_owner_uploaded': function(newVal, oldVal) {
      if(newVal === 1) {
        // 原作所有权
        this.form.is_original = 1;

        this.authorList.push(this.userAuthor);
        this.form.poet_id = 'new_' + this.form._user_name;
        // this.form.poet_id = null;
        this.form.poet = this.form._user_name;
        this.form.poet_cn = this.form._user_name;
      } else if(newVal === 2) {
        // 译作所有权
        this.form.is_original = 0;
        this.translatorList.push(this.userAuthor);
        this.form.translator = this.form._user_name;
      }

    },

    'form.poet_id': function (newVal) {
      if(newVal === null) {
        this.form.poet = '';
        this.form.poet_cn = '';
        console.log('clear poet');
      }
    },

    'form.translator_id': function (newVal) {
      if(newVal === null) {
        this.form.translator = '';
        console.log('clear translator');
      }
    },

  },

  mounted: function() {
    if(!this.form.poet_id && this.form.poet) {
      this.authorList.push(this.newAuthor);
      this.form.poet_id = 'new_' + this.form.poet;
    }
    if(!this.form.translator_id && this.form.translator) {
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
      if(data.is_original === 1) {
        console.log('clear translator info on submit');
        data.translator_id = null;
        data.translator_wikidata_id = null;
        data.translator = null;
        delete data.original_link;
        delete data.original_id;
      }
      return data;
    },

    onSelectPoet: function(option) {
      this.form.poet = option.label;
      this.form.poet_cn = option.label_cn||option.label;
      console.log('use new poet_cn:', this.form.poet_cn, option)
      if(this.isNew(this.form.poet_id)) {
        this.form.poet_wikidata_id = null;
      }
      console.log('selected poet', option, this.form.poet, this.form.poet_cn, this.form.poet_id);
    },
    onSelectTranslator: function(option) {
      this.form.translator = option.label;
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
      if(!search) {
        loading(false);
        return
      }

      axios(
        `/q/author/${encodeURI(search)}/` + (vm.form[field] || '')
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
    }, 500),
    searchTranslator: _.debounce((field, loading, search, vm) => {
      if(!search) {
        loading(false);
        return
      }
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
    },

    userAuthor() {
      return {
        id: 'new_' + this.form._user_name,
        label: this.form._user_name,
        label_en: this.form._user_name,
        label_cn: this.form._user_name,
        url: '',
        source: '',
        avatar_url: '/images/avatar-default.png'
      }
    },
    newAuthor() {
      return {
        id: 'new_' + this.form.poet,
        label: this.form.poet,
        label_en: this.form.poet,
        label_cn: this.form.poet,
        url: '',
        source: '',
        avatar_url: '/images/avatar-default.png'
      }
    },
    newTranslator() {
      return {
        id: 'new_' + this.form.translator,
        label: this.form.translator,
        label_en: this.form.translator,
        label_cn: this.form.translator,
        url: '',
        source: '',
        avatar_url: '/images/avatar-default.png'
      }
    }
  },

});
