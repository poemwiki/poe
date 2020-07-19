import Vue from 'vue';
//import ElementUI from 'element-ui';
//import { Message } from 'element-ui';
import clipboard from 'clipboard';
require('./bootstrap');


//Vue.use(ElementUI, {
//  size: 'medium'
//});

new Vue({
  el: '#app',
  mounted() {
    let board = new clipboard('#copy');
    let delay = 1000;
    let $copy = $('#copy');
    board.on('success', function(e) {
      $copy.tooltip({
        title: '复制成功',
        trigger: 'click'
      });
      $copy.tooltip('show');
      window.setTimeout(() => {
        $copy.tooltip('dispose');
      }, delay);

      e.clearSelection();
    });

    board.on('error', function(e) {
      $copy.tooltip({
        title: "请复制以下链接：\n" + e.text,
        trigger: 'click'
      });
      $copy.tooltip('show');
      window.setTimeout(() => {
        $copy.tooltip('hide');
      }, delay);
    });
  },

  methods: {

  }
});
