import Vue from 'vue';
// import '../bootstrap.js';
import VueElementLoading from 'vue-element-loading';
import BaseForm from '../components/BaseForm';

Vue.component('author-form', {
  components: {
    VueElementLoading
  },
  mixins: [BaseForm],
  data: function () {
    return {
      form: {
        name_lang: '',
        nation_id: '',
        dynasty_id: ''
      }
    }
  },

  mounted: function() {
    for(const locale of this.locales) {
      for(const field of ['name_lang', 'describe_lang']) {
        const fieldName = field + '_' + locale;
        this.form[field][locale] = this.$el.querySelector('[name="'+fieldName+'"]').getAttribute('value') || this.form[field][locale];
      }
    }
    this.form.nation_id = this.$el.querySelector('[name="nation_id"]').getAttribute('value') || this.form.nation_id;
    this.form.dynasty_id = this.$el.querySelector('[name="dynasty_id"]').getAttribute('value') || this.form.dynasty_id;

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
    }
  },
  computed: {
  },

});
