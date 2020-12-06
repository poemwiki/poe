import Vue from 'vue';
import '../bootstrap.js';
import LunarFullCalendar from "vue-lunar-full-calendar";
import VueElementLoading from 'vue-element-loading'


Vue.use(LunarFullCalendar);

new Vue({
  components: {
    VueElementLoading
  },
  el: '#calendar-page',

  data() {
    return {
      config: {

        lunarCalendar: true,
        defaultView: 'month',
        contentHeight: 'auto',
        titleFormat: "YYYY年M月",
        header: {
          left: 'prev,next'
        }
      },
      birth: null,
      death: null,
      month: null,
      day: null,
      currentYear: new Date().getFullYear()
    }
  },

  mounted() {
    var current = new Date();
    this.month = current.getMonth() + 1;
    this.day = current.getDate();
    axios('/calendar/q/' + this.month + '/' + this.day).then(reply => {
      var data = reply.data;
      this.birth = data.birth;
      this.death = data.death;
    })
  },
  filters: {
    doted: function (value) {
      if(!value) return '?';
      return value.replace(/-/g, '.').substr(0,7).replace(/^0+/, ' ');
    }
  },
  methods: {
    selected(date) {
      console.log(date.month() + 1, date.date());
      this.month = date.month() + 1;
      this.day = date.date();
      this.birth = null;
      this.death = null;
      axios('/calendar/q/' + (date.month() + 1) + '/' + date.date()).then(reply => {
        var data = reply.data;
        this.birth = data.birth;
        this.death = data.death;
      });
    }

  }
});