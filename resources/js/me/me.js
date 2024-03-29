import '../bootstrap';
import CalendarHeat from './CalendarHeat.vue';

new Vue({
  el: '#app',
  components: {CalendarHeat},
  data: {
    firstLoaded: false,
    contributions: {},
    originalPoems: [],
    currentPage: 1,
    stopFetchMorePoems: false,
    userID: '',
    originalPoemsTotal: null,
    loading: true,
  },
  async mounted() {
    this.userID = this.$refs.userID.textContent;
    const response = await this.fetchPoems();
    this.originalPoems = response.data;
    this.originalPoemsTotal = response.total;
  },

  watch: {
    contributions(newVal) {
      if(!this.firstLoaded) {
        this.$refs.contributionCount.textContent = Object.keys(newVal).reduce((acc, key) => {
          return acc + newVal[key].length;
        }, 0);
        this.firstLoaded = true;
      }
    }
  },

  methods: {
    onScroll: async function() {
      if (this.stopFetchMorePoems) {
        return
      }

      const bottomOfWindow =
        this.$refs.page.parentElement.scrollTop + window.innerHeight > this.$refs.page.offsetHeight;
      if(!bottomOfWindow) {
        return;
      }

      this.stopFetchMorePoems = true;
      const res = await this.fetchPoems(this.currentPage+1);
      this.originalPoems = this.originalPoems.concat(res.data);

      this.currentPage++;

      // rate limit
      setTimeout(() => {
        this.stopFetchMorePoems = !res.has_more_pages;
      }, 1000);
    },

    _fetchPoems: async function(page = 1) {
      const url = `/api/v1/poem/user/${this.userID}/${page}/10`;
      try {
        const resp = await axios.get(url);

        if(resp.code !== 0) {
          return [];
        }

        return resp.data;
      } catch (error) {
        console.error(error);
      }
    },

    fetchPoems: async function(page = 1) {
      this.loading = true;
      const res = await this._fetchPoems(page);
      this.loading = false;
      return res;
    },

    fetchContributions: async function(userID) {
      const endDay = new Date();
      const endDateString = new Date().toISOString().slice(0, 10);
      // a year ago
      const startDay = new Date(new Date().setDate(endDay.getDate() - 365));
      const startDateString = startDay.toISOString().slice(0, 10);

      const url = `/api/v1/contribution?user=${userID}&date-from=${startDateString}&date-to=${endDateString}`;

      try {
        const resp = await axios.get(url);

        if(resp.code !== 0) {
          this.contributions = [];
        }

        this.contributions = resp.data;
      } catch (error) {
        this.contributions = {'2022-1-1': []};
        console.error(error);
      }

      // from startDay to endDay, if this.contributions[date] doesn't exist,
      // set this.contributions[date] with an empty array
      for (let d = new Date(endDay.getTime()); d >= startDay; d.setDate(d.getDate() - 1)) {
        const date = d.toISOString().slice(0, 10);
        if (!this.contributions[date]) {
          this.contributions[date] = [];
        }
      }

      return Object.keys(this.contributions).map(key => {
        return [
          key,
          this.contributions[key].length,
        ];
      });
    }
  }
});
