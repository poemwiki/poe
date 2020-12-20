'use strict';

Object.defineProperty(exports, "__esModule", {
  value: true
});

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) {
  return typeof obj;
} : function (obj) {
  return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
};

var _moment = require('moment');

var _moment2 = _interopRequireDefault(_moment);

function _interopRequireDefault(obj) {
  return obj && obj.__esModule ? obj : {default: obj};
}


var BaseForm = {
  props: {
    action: {
      type: String,
      required: true
    },
    locales: {
      type: Array
    },
    trans: {
      type: Object,
      default: function _default() {
        return {};
      }
    },
    defaultLocale: {
      type: String,
      default: function _default() {
        return document.documentElement.lang ? document.documentElement.lang
          : (this.locales instanceof Array && this.locales.length > 0 ? this.locales[0] : '');
      }
    },
    sendEmptyLocales: {
      type: Boolean,
      default: function _default() {
        return true;
      }
    },
    data: {
      type: Object,
      default: function _default() {
        return {};
      }
    },
    responsiveBreakpoint: {
      type: Number,
      default: 850
    }
  },
  components: {
    // 'user-detail-tooltip': _UserDetailTooltip2.default
  },

  created: function created() {
    if (!!this.locales && this.locales.length > 0) {
      var form = this.form;
      // this.locales.map(function(l) {
      //     if (!_.has(form, l)) {
      //         _.set(form, l, {})
      //     }
      // })
      this.currentLocale = this.defaultLocale;
    }

    //FIXME: now we can't add dynamic input in update type of form
    if (!_.isEmpty(this.data)) {
      this.form = this.data;
    }
    window.addEventListener('resize', this.onResize);
  },

  data: function data() {

    return {
      form: {},
      lang: this.trans,
      mediaCollections: [],
      isFormLocalized: false,
      currentLocale: '',
      submiting: false,
      onSmallScreen: window.innerWidth < this.responsiveBreakpoint,
      datePickerConfig: {
        dateFormat: 'Y-m-d H:i:S',
        altInput: true,
        altFormat: 'd.m.Y',
        locale: null
      },
      timePickerConfig: {
        enableTime: true,
        noCalendar: true,
        time_24hr: true,
        enableSeconds: true,
        dateFormat: 'H:i:S',
        altInput: true,
        altFormat: 'H:i:S',
        locale: null
      },
      datetimePickerConfig: {
        enableTime: true,
        time_24hr: true,
        enableSeconds: true,
        dateFormat: 'Y-m-d H:i:S',
        altInput: true,
        altFormat: 'd.m.Y H:i:S',
        locale: null
      }
    };
  },

  computed: {
    otherLocales: function otherLocales() {
      var _this = this;

      return this.locales.filter(function (x) {
        return x != _this.defaultLocale;
      });
    },
    showLocalizedValidationError: function showLocalizedValidationError() {
      var _this2 = this;

      // TODO ked sme neni na mobile, tak pozerat zo vsetkych
      return this.otherLocales.some(function (lang) {
        return _this2.errors.items.some(function (item) {
          return item.field.endsWith('_' + lang) || item.field.startsWith(lang + '_');
        });
      });
    }
  },
  filters: {
    date: function date(date) {
      var format = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'YYYY-MM-DD';

      var date = (0, _moment2.default)(date);
      return date.isValid() ? date.format(format) : "";
    },
    datetime: function datetime(_datetime) {
      var format = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'YYYY-MM-DD HH:mm:ss';

      var date = (0, _moment2.default)(_datetime);
      return date.isValid() ? date.format(format) : "";
    },
    time: function time(_time) {
      var format = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'HH:mm:ss';

      // '2000-01-01' is here just because momentjs needs a date
      var date = (0, _moment2.default)('2000-01-01 ' + _time);
      return date.isValid() ? date.format(format) : "";
    }
  },
  methods: {
    getPostData: function getPostData() {
      var _this3 = this;

      if (this.mediaCollections) {
        this.mediaCollections.forEach(function (collection, index, arr) {
          if (_this3.form[collection]) {
            console.warn("MediaUploader warning: Media input must have a unique name, '" + collection + "' is already defined in regular inputs.");
          }

          if (_this3.$refs[collection + '_uploader']) {
            _this3.form[collection] = _this3.$refs[collection + '_uploader'].getFiles();
          }
        });
      }
      this.form['wysiwygMedia'] = this.wysiwygMedia;

      return this.form;
    },
    onSubmit: function onSubmit() {
      var _this4 = this;

      return this.$validator.validateAll().then(function (result) {
        if (!result) {
          _this4.$notify({
            type: 'error',
            title: 'Error!',
            text: 'The form contains invalid fields.'
          });
          return false;
        }

        var data = _this4.form;
        if (!_this4.sendEmptyLocales) {
          data = _.omit(_this4.form, _this4.locales.filter(function (locale) {
            return _.isEmpty(_this4.form[locale]);
          }));
        }

        _this4.submiting = true;

        return axios.post(_this4.action, _this4.getPostData()).then(function (response) {
          return _this4.onSuccess(response);
        }).catch(function (errors) {
          return _this4.onFail(errors.response.data);
        });
      });
    },
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
    onFail: function onFail(data) {
      this.submiting = false;
      if (_typeof(data.errors) !== (typeof undefined === 'undefined' ? 'undefined' : _typeof(undefined))) {
        var bag = this.$validator.errors;
        bag.clear();
        Object.keys(data.errors).map(function (key) {
          var splitted = key.split('.', 2);
          // we assume that first dot divides column and locale (TODO maybe refactor this and make it more general)
          if (splitted.length > 1) {
            bag.add({
              field: splitted[0] + '_' + splitted[1],
              msg: data.errors[key][0]
            });
          } else {
            bag.add({
              field: key,
              msg: data.errors[key][0]
            });
          }
        });
        if (_typeof(data.message) === (typeof undefined === 'undefined' ? 'undefined' : _typeof(undefined))) {
          this.$notify({
            type: 'error',
            title: 'Error!',
            text: 'The form contains invalid fields.'
          });
        }
      }
      if (_typeof(data.message) !== (typeof undefined === 'undefined' ? 'undefined' : _typeof(undefined))) {
        this.$notify({
          type: 'error',
          title: 'Error!',
          text: data.message
        });
      }
    },
    getLocalizedFormDefaults: function getLocalizedFormDefaults() {
      var object = {};
      this.locales.forEach(function (currentValue, index, arr) {
        object[currentValue] = null;
      });
      return object;
    },
    showLocalization: function showLocalization() {
      this.isFormLocalized = true;
      this.currentLocale = this.otherLocales[0];
      $('.container-xl').addClass('width-auto');
    },
    hideLocalization: function hideLocalization() {
      this.isFormLocalized = false;
      $('.container-xl').removeClass('width-auto');
    },
    validate: function validate(event) {
      this.$validator.errors.remove(event.target.name);
    },
    shouldShowLangGroup: function shouldShowLangGroup(locale) {
      if (!this.onSmallScreen) {
        if (this.defaultLocale == locale) return true;

        return this.isFormLocalized && this.currentLocale == locale;
      } else {
        return this.currentLocale == locale;
      }
    },
    onResize: function onResize() {
      this.onSmallScreen = window.innerWidth < this.responsiveBreakpoint;
    }
  }
};

exports.default = BaseForm;