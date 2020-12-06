window._ = require('lodash');

/**
 * We'll load jQuery and the Bootstrap jQuery plugin which provides support
 * for JavaScript based Bootstrap features such as modals and tabs. This
 * code may be modified to fit the specific needs of your application.
 */

try {
    window.Popper = require('popper.js').default;
    // window.$ = window.zepto = require('zepto');

    // require('bootstrap');
} catch (e) {}

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

window.axios = require('axios');

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

// usage
const responseInterceptor = [
  res => res.data,
  error => {

    if(error.response.status === 429) {
      alert('Too many request! Try again later.');
      return;
    }
    if(error.response.status !== 200) {
      console.error('Unkown error. Try again later.');
      console.error(error.response.status);
      console.error(error.response.data);
    }
    console.error(error.response.data.message);
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
