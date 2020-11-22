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
  }
});