import '../bootstrap';
import CalendarHeat from './CalendarHeat.vue';

new Vue({
  el: '#app',
  components: {CalendarHeat},
  data: {
    firstLoaded: false,
    contributions: [],
  },

  watch: {
    contributions(newVal) {
      if(!this.firstLoaded) {
        this.$refs.contributionCount.textContent = Object.keys(this.contributions).length;
        this.firstLoaded = true;
      }
    }
  },

  methods: {
    async fetchContributions(userID) {
      const endDay = new Date();
      const endDateString = new Date().toISOString().slice(0, 10);
      // a year ago
      const startDay = new Date(new Date().setDate(endDay.getDate() - 365));
      const startDateString = startDay.toISOString().slice(0, 10);

      const url = `/api/v1/contribution?user=${userID}&date-from=${startDateString}&date-to=${endDateString}`;
      this.contributions = await axios.get(url);

      // from startDay to endDay, if this.contributions[date] doesn't exist,
      // set this.contributions[date] with an empty array
      for (let d = new Date(endDay.getTime()); d >= startDay; d.setDate(d.getDate() - 1)) {
        const date = d.toISOString().slice(0, 10);
        if (!this.contributions[date]) {
          this.contributions[date] = [];
        }
      }

      const contributions = Object.keys(this.contributions).map(key => {
        return [
          key,
          this.contributions[key].length,
        ];
      });
      console.log('contributions', contributions);
      return contributions;
    }
  }
});
