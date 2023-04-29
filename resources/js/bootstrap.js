import _ from 'lodash';
import axios from 'axios';
import Vue from 'vue';
import Notifications from 'vue-notification';
window._ = _;
window.Vue = Vue;
Vue.use(Notifications);

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */
window.axios = axios;

const _createAxios = axios.create;

axios.create = function createPatchedAxios(conf) {
  const instance = _createAxios(conf);
  const defaultIcs = axios.defaults.interceptors;

  const resInterceptor = defaultIcs && defaultIcs.response ? defaultIcs.response : false;
  const reqInterceptor = defaultIcs && defaultIcs.request ? defaultIcs.request : false;

  if (reqInterceptor) instance.interceptors.request.use(...reqInterceptor);
  if (resInterceptor) instance.interceptors.response.use(...resInterceptor);
  return instance;
};

const responseInterceptor = [
  res => {
    return res.data;
  },

  // Handle request errors here,
  // handle application errors(status === 200 but some wrong happened) after each request
  error => {
    if(error.response.status === 422) {
      const text = [];
      const data = error.response.data
      Object.keys(error.response.data.errors).map(function (key) {
        text.push(data.errors[key][0]);
      });

      Vue.notify({
        type: 'error',
        title: 'Error while processing your request!',
        text: text.join("<br/>")
      });
      console.error(error.response.data);
      return;
    }

    if(error.response.status !== 200) {
      Vue.notify({
        type: 'error',
        title: 'Error',
        text: `Code: ${error.response.status} ${error.response.statusText}`
      });
    }

    console.error('Response Error', error.response);
  }
];

axios.defaults.interceptors = {
  response: responseInterceptor
};

// make sure the default exported instance also uses the interceptors
axios.interceptors.response.use(...responseInterceptor);

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// import Echo from 'laravel-echo';

// window.Pusher = require('pusher-js');

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: process.env.MIX_PUSHER_APP_KEY,
//     cluster: process.env.MIX_PUSHER_APP_CLUSTER,
//     forceTLS: true
// });
