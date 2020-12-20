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
        name_lang: this.getLocalizedFormDefaults(),
        describe_lang: this.getLocalizedFormDefaults(),
        nation_id: '',
        dynasty_id: ''
      }
    }
  },

  watch: {
    'form.nation_id': function (newId, old) {
      if(newId !== 1) {
        this.form.dynasty_id = '';
      }
    }
  },

  mounted: function() {
    // this.form.nation_id = this.$el.querySelector('[name="nation_id"]').getAttribute('value') || this.form.nation_id;
    // this.form.dynasty_id = this.$el.querySelector('[name="dynasty_id"]').getAttribute('value') || this.form.dynasty_id;
  },

  methods: {
    onSuccess: function onSuccess(data) {

      if (data.code === 0) {
        this.$notify({
          type: 'success',
          title: '操作成功',
          text: '您的修改已提交。' + (data.redirect ? '正在跳转到作者页...' : '')
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
    }
  },
  computed: {
  },

});
