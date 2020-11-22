import AppListing from '../app-components/Listing/AppListing';
import {BaseListing} from 'craftable';

Vue.component('poem-listing', {
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