import '../bootstrap';

// import 'flatpickr/dist/flatpickr.css';
// import flatPickr from 'vue-flatpickr-component';

// import 'vue-multiselect/dist/vue-multiselect.min.css';
// import Multiselect from 'vue-multiselect';
import VeeValidate, { Validator } from 'vee-validate';
import zh_CN from "vee-validate/dist/locale/zh_CN";
import en from "vee-validate/dist/locale/en";
// import VModal from 'vue-js-modal';

// Vue.component('multiselect', Multiselect);
Vue.use(VeeValidate, {strict: true});

zh_CN.messages.required = function(n) {
  return n+"是必填的";
};
let lang = document.getElementsByTagName('html')[0].lang;
if (lang === 'zh-CN') {
  Validator.localize("zh_CN", zh_CN);
} else {
  Validator.localize("en", en);
}

// Vue.component('datetime', flatPickr);
// Vue.use(VModal, { dialog: true, dynamic: true, injectModalsContainer: true });

Vue.directive('visible', function(el, binding) {
  el.style.opacity = !!binding.value ? '1' : '0';
});

new Vue({
  el: '#app',
  data: function data() {
    return {
      loading: false
    };
  },
  mounted: function mounted() {
    var _this = this;

    // Add a loader request interceptor
    axios.interceptors.request.use(function (config) {
      _this.setLoading(true);
      return config;
    }, function (error) {
      _this.setLoading(false);
      return Promise.reject(error);
    });

    // Add a loader response interceptor
    axios.interceptors.response.use(function (response) {
      _this.setLoading(false);
      return response;
    }, function (error) {
      _this.setLoading(false);
      return Promise.reject(error);
    });
  },

  methods: {
    setLoading: function setLoading(value) {
      this.loading = !!value;
    }
  }
});

import './Form';