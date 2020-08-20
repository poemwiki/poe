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
        is_original: false,
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

  methods: {
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
