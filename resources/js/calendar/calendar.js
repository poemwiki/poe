import Vue from 'vue';
import '../bootstrap.js';
import LunarFullCalendar from "vue-lunar-full-calendar";
import VueElementLoading from 'vue-element-loading';


Vue.use(LunarFullCalendar);

new Vue({
  components: {
    VueElementLoading
  },
  el: '#calendar-page',

  data() {
    return {
      loading: true,
      config: {
        lunarCalendar: true,
        defaultView: 'month',
        contentHeight: 'auto',
        titleFormat: "YYYY年M月",
        header: {
          left: 'prev,next'
        },
        selectable: false,
        selectHelper: false,
        editable: false
      },
      events: [],
      birth: null,
      death: null,
      year: null,
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
      this.loading = false;
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
      this.loading = true;
      var $prev = this.$refs.calendar.$el.querySelectorAll('.fc-highlight');
      if($prev) {
        _.each($prev, function ($el) {
          $el.classList.remove('fc-highlight');
        })
      }

      this.year = date.year();
      this.month = date.month() + 1;
      this.day = date.date();
      this.birth = null;
      this.death = null;

      $(this.$refs.calendar.$el).find(`td[data-date="${date.format()}"]`).addClass('fc-highlight');

      axios('/calendar/q/' + (date.month() + 1) + '/' + date.date()).then(reply => {
        if(!reply || !reply.data) return;

        var data = reply.data;
        this.birth = data.birth;
        this.death = data.death;
        this.loading = false;
      }).finally(() => {
        this.loading = false;
      });
    }

  }
});