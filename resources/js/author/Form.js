import Vue from 'vue';
// import '../bootstrap.js';
import VueElementLoading from 'vue-element-loading';
import BaseForm from '../components/BaseForm';
import vSelect from 'vue-select';
vSelect.props.reduce.default = function (option) {
  return option.id;
};

Vue.component('author-form', {
  components: {
    VueElementLoading, vSelect
  },
  mixins: [BaseForm],
  props: ['dynastyList', 'defaultNation'],
  data: function () {
    return {
      form: {
        name_lang: this.getLocalizedFormDefaults(),
        describe_lang: this.getLocalizedFormDefaults(),
        nation_id: null,
        dynasty_id: null
      },
      nationList: this.defaultNation,
      avatarFile: null
    }
  },

  watch: {
    'form.nation_id': function (newId, old) {
      if(newId !== 32) {
        console.log(newId);
        this.form.dynasty_id = '';
      }
    }
  },

  mounted: function() {
  },

  methods: {

    onAvatarChange: function (e) {
      this.avatarFile = e.target.files[0];

      var fd = new FormData();
      fd.append('avatar', this.avatarFile, this.avatarFile.name);
      fd.append('author', this.form.id);

      axios.post('/author/avatar', fd).then(res => {
        if(!res) throw '上传失败。';
        console.log('upload author avatar finished', res);
        this.form.avatar = res.data.avatar;
      }).catch(e => {
        window.alert('上传失败。')
      });

      console.log(this.avatarFile, e);
    },

    getPostData: function () {
      let data = _.clone(this.form);
      if(data.name_lang.en === null) delete data.name_lang.en;
      if(data.describe_lang.en === null) data.describe_lang.en = '';
      if(data.describe_lang['zh-CN'] === null) data.describe_lang['zh-CN'] = '';

      return data;
    },

    onSearchNation: function(keyword, loading) {
        if(keyword.length) {
          loading(true);
          this.searchNation(loading, keyword, this);
        }
    },
    searchNation: _.debounce((loading, search, vm) => {
      axios(
        `/q/nation/${encodeURI(search)}/${vm.form.nation_id}`
      ).then(res => {
        if(res.data.length)
          vm.nationList = res.data;
        loading(false);
      });
    }, 350),

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
