import AppForm from '../app-components/Form/AppForm';
import { codemirror } from 'vue-codemirror';// import language js

// import base style
import 'codemirror/lib/codemirror.css'

Vue.component('poem-form', {
  components: {
    codemirror
  },
  mixins: [AppForm],
  data: function () {
    return {
      form: {
        title: '',
        language: false,
        is_original: 1,
        poet: '',
        poet_cn: '',
        bedtime_post_id:  '' ,
        bedtime_post_title:  '' ,
        poem: '',
        length: '',
        translator: '',
        from:  '' ,
        year:  '' ,
        // month:  '' ,
        // date:  '' ,
        dynasty: '',
        nation: '',
        // need_confirm:  false ,
        // is_lock:  false ,
        // content_id:  '' ,
        original_id: '',
        translated_id: ''
      },

      cmOptions: {
        tabSize: 2,
        mode: 'text/plain',
        lineNumbers: true,
        line: true,
        // more CodeMirror options...
      }
    }
  },

  mounted: function() {
    this.form.poet_cn = this.$el.querySelector('[name="poet_cn"]').getAttribute('value') || this.form.poet_cn;
    this.form.poet = this.$el.querySelector('[name="poet"]').getAttribute('value') || this.form.poet;
    this.form.nation = this.$el.querySelector('[name="nation"]').getAttribute('value') || this.form.nation;
    this.form.dynasty = this.$el.querySelector('[name="dynasty"]').getAttribute('value') || this.form.dynasty;
    this.form.from = this.$el.querySelector('[name="from"]').getAttribute('value') || this.form.from;
    this.form.year = this.$el.querySelector('[name="year"]').getAttribute('value') || this.form.year;
    this.form.is_original = this.$el.querySelector('[name="is_original_fake_element"]').getAttribute('value') || this.form.is_original;
    this.form.bedtime_post_id = this.$el.querySelector('[name="bedtime_post_id"]')?.getAttribute('value') || this.form.bedtime_post_id;
    this.form.bedtime_post_title = this.$el.querySelector('[name="bedtime_post_title"]')?.getAttribute('value') || this.form.bedtime_post_title;
    this.form.original_id = this.$el.querySelector('[name="original_id"]')?.getAttribute('value') || this.form.original_id;
    this.form.translated_id = this.$el.querySelector('[name="translated_id"]')?.getAttribute('value') || this.form.translated_id;

  },

  methods: {
    onSuccess: function onSuccess(data) {
      this.submiting = false;
      if (data.code === 0) {
        this.$notify({
          type: 'success',
          title: '操作成功',
          text: '您的修改已提交。'
        });
        if(data.redirect){
          location.href = data.redirect;
        }
      }
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
