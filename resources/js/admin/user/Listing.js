import AppListing from '../app-components/Listing/AppListing';

Vue.component('user-listing', {
  mixins: [AppListing],
  data: function data() {
    return {
      orderBy: {
        column: 'updated_at',
        direction: 'desc'
      },
    }
  },
  methods: {
    bindRefType: function(ref) {
      const refs = {0: '微信浏览器', 1: '预留绑定类型1', 2: '微信小程序'}
      return refs[ref] || '无绑定';
    }
  }
});