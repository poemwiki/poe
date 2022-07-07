import './bootstrap';

import 'vue-multiselect/dist/vue-multiselect.min.css';
import flatPickr from 'vue-flatpickr-component';
import VueQuillEditor from 'vue-quill-editor';
import Notifications from 'vue-notification';
import Multiselect from 'vue-multiselect';
import VeeValidate, { Validator } from 'vee-validate';
import zh_CN from "vee-validate/dist/locale/zh_CN";
import en from "vee-validate/dist/locale/en";
import 'flatpickr/dist/flatpickr.css';
import VueCookie from 'vue-cookie';
import { Admin } from 'craftable';
import VModal from 'vue-js-modal'
import Vue from 'vue';

import './app-components/bootstrap';
import './index';

import 'craftable/dist/ui';

Vue.component('multiselect', Multiselect);
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
Vue.filter('lang', function (value) {
  if (!value) return '';
  value = JSON.parse(value.toString())
  return value[lang];
});

function niceDateTime(dateStr) {
  var date = Date.parse(dateStr);
  return new Intl.DateTimeFormat('zh', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hourCycle: 'h23'
  }).format(date).replace(/\//g, '-');
}
Vue.filter('niceDateTime', function (v) {
  return niceDateTime(v);
})


Vue.component('datetime', flatPickr);
Vue.use(VModal, { dialog: true, dynamic: true, injectModalsContainer: true });
Vue.use(VueQuillEditor);
Vue.use(Notifications);
Vue.use(VueCookie);

new Vue({
    mixins: [Admin],
});
