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
        poet_id: '',
        translator_id: '',
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
    // this.form.is_original = this.$el.querySelector('[name="is_original_fake_element"]').getAttribute('value') || this.form.is_original;
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
