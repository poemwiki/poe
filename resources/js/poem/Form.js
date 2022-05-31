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
  props: ['defaultAuthors', 'defaultTranslators'],
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
        translator_ids: null,
        poet_wikidata_id: null,
        translator_wikidata_id: null,
        is_owner_uploaded: 0,
        '#user_name': '',
        original_link: ''
      },

      authorList: this.defaultAuthors,
      translatorList: this.defaultTranslators,
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
              // if previous character is [a-zA-Z0-9], do not replace
              var prevChar = doc.getLine(cursor.line).slice(cursor.ch-1, cursor.ch);
              var nextChar = doc.getLine(cursor.line).slice(cursor.ch, cursor.ch+1);
              // TODO test for all none CJK characters
              if(/[a-zA-Z0-9]+$/.test(prevChar) || /[a-zA-Z0-9]+$/.test(nextChar)) {
                doc.replaceRange(' ', pos);
              } else {
                doc.replaceRange('　', pos);
              }
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
        this.form.poet_id = 'new_' + this.form['#user_name'];
        // this.form.poet_id = null;
        this.form.poet = this.form['#user_name'];
        this.form.poet_cn = this.form['#user_name'];
      } else if(newVal === 2) {
        // 译作所有权
        this.form.is_original = 0;
        this.translatorList.push(this.userAuthor);
        this.form.translator = this.form['#user_name'];
      }

    },

    'form.poet_id': function (newVal) {
      if(newVal === null) {
        this.form.poet = '';
        this.form.poet_cn = '';
        console.log('clear poet');
      }
    },

    // 'form.translator_ids': function (newVal) {
    //   if(newVal === null) {
    //     this.form.translator = '';
    //     console.log('clear translator');
    //   }
    // },

  },

  mounted: function() {
    if(!this.form.poet_id && this.form.poet) {
      this.authorList.push(this.newAuthor);
      this.form.poet_id = 'new_' + this.form.poet;
    }

    if(!this.form.translator_ids && this.form.translator) {
      this.form.translator_ids = [];
      this.form['#translators_label_arr'].forEach((translator) => {
        var id = translator.id ? translator.id : 'new_' + translator.name
        this.form.translator_ids.push(id)

        if(!translator.id) {
          this.translatorList.push(this.getNewTranslator(translator.name, translator.id));
        }
      });
    }

    this.form.agree = true;
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

      if(this.isNew(data.poet_id)) {
        data.poet_id = 'new';
        data.poet_wikidata_id = null; // you need to set wikidata_id null here because initial wikidata_id may not null
      }

      if(Array.isArray(data.translator_ids)) {
        data.translator_ids.forEach((id, index) => {

          // if(this.isNew(id)) {
          //   data.translator_ids[index] = 'new';
          //   data.translator_wikidata_id = null;
          // }
        });
      }

      if(data.is_original === 1) {
        console.log('clear translator info on submit');
        data.translator_ids = null;
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
      console.log('selected poet option', option);
      console.log(this.form.poet, this.form.poet_cn, this.form.poet_id);
    },
    onSelectTranslator: function(option) {
      console.log('onselectTranslator', option);
      this.form.translator = option[option.length-1].label;
    },
    onDeselectTranslator: function(option) {
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

      // TODO 获取焦点时请求一次查询
      // if(this.isNew(this.form.translator_ids) && query === undefined) {
      //   loading(true);
      //   this.searchTranslator('translator_ids', loading, this.form.translator, this);
      // }
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
        this.searchTranslator('translator_ids', loading, keyword, this);
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
        vm.translatorList = res.data.map(item => {
          if(vm.isNew(item.id)) {
            return vm.getNewTranslator(item.id.slice(4));
          }
          return item;
        });
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
    },

    onCmBeforechange(cm, change) {
      // console.log('beforeChange', cm, change.text.join("\n"))

      // clean unnecessary empty lines
      if(change.origin === 'paste') {
        // TOOD replace spaceX2~4 to full width space
        let lineCount=0, lenthSum=0, emptyLineCount=0, minStartSpace=0, spaceStartLineCount=0;
        change.text.forEach(line => {
          lineCount++;
          lenthSum += line.length;
          if(isEmptyLine(line)) emptyLineCount++;

          if(startWithSpace(line)) spaceStartLineCount++;

          var startSpace = startSpaceNum(line)
          minStartSpace = startSpace < minStartSpace ? startSpace : minStartSpace;

        });
        const avgLength = lenthSum / (lineCount - emptyLineCount);

        console.log({emptyLineCount, lineCount, textLineCount: lineCount-emptyLineCount, avgLength})

        // TODO write a same function for server side empty line clean and detect
        // remove redundant empty lines
        if(emptyLineCount >= (lineCount - emptyLineCount -1) && avgLength<70) {
          const delMark = '##_@DELETE@_##';
          let newText = change.text.map((line, index) => {
            if(isEmptyLine(line)) {
              const nextLine = index < change.text.length - 1 ? change.text[index + 1] : null;

              if(nextLine!==null && !isEmptyLine(nextLine)) {
                return delMark;
              }
            }
            return line.replace(/\s+$/g, '');
          })

          newText = newText.map((line, index) => {
            const prevLine = index >= 1 ? newText[index - 1] : null;
            const nextLine = index < newText.length - 1 ? newText[index + 1] : null;

            if(prevLine!==null && isEmptyLine(prevLine) && nextLine && nextLine===delMark) {
              return delMark;
            }
            return line;
          }).filter(line => line!==delMark);

          console.log('empty line cleaned:', newText)
          change.update(null, null, newText);
        }

        // remove space before each line
        var newText = change.text.map((line, index) => {
          if (spaceStartLineCount >= lineCount-emptyLineCount-2) {
            return line.trim();
          }
          return line.trimRight();
        })
        change.update(null, null, newText);



        function shouldDel(prevLine, nextLine) {
          return !isEmptyLine(prevLine) && !isEmptyLine(nextLine)
        }

        function isEmptyLine(str) {
          return !str.trim().length;
        }

        function startWithSpace(str) {
          return /^\s/.test(str)
        }

        function startSpaceNum(str) {
          return str.length - str.trimLeft().length;
        }
      }
    },

    getNewTranslator(label, id=null) {
      return {
        id: id ? id : 'new_' + label,
        label: label,
        label_en: label,
        label_cn: label,
        url: '',
        source: id ? 'PoemWiki' : '',
        avatar_url: '/images/avatar-default.png'
      }
    }
  },
  computed: {
    codemirror() {
      return this.$refs.cmEditor.codemirror;
    },

    userAuthor() {
      return {
        id: 'new_' + this.form['#user_name'],
        label: this.form['#user_name'],
        label_en: this.form['#user_name'],
        label_cn: this.form['#user_name'],
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