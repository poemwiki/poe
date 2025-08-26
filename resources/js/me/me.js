import '../bootstrap';
import CalendarHeat from './CalendarHeat.vue';
import LoadingBox from '../components/LoadingBox.vue';

new Vue({
  el: '#app',
  components: {CalendarHeat, LoadingBox},
  data: {
    firstLoaded: false,
    contributions: {},
    originalPoems: [],
    currentPage: 1,
    stopFetchMorePoems: false,
    userID: '',
    originalPoemsTotal: null,
    loading: true,
  fiveStarPoems: [],
  fiveStarPage: 1,
  fiveStarStop: false,
  fiveStarLoading: false,
  },
  async mounted() {
    this.userID = this.$refs.userID.textContent;
    const response = await this.fetchPoems();
    this.originalPoems = response.data;
    this.originalPoemsTotal = response.total;
  this.fetchFiveStarPoems();
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
      const container = document.getElementById('app');
      if(!container) return;
      const nearBottom = container.scrollTop + container.clientHeight > container.scrollHeight - 300;
      if(!nearBottom) return;

      // original poems infinite load
      if(!this.stopFetchMorePoems) {
        this.stopFetchMorePoems = true;
        const res = await this.fetchPoems(this.currentPage+1);
        this.originalPoems = this.originalPoems.concat(res.data);
        this.currentPage++;
        setTimeout(()=>{ this.stopFetchMorePoems = !res.has_more_pages; }, 800);
      }
      // five star poems infinite load
      if(!this.fiveStarStop && !this.fiveStarLoading) {
        const page = this.fiveStarPage + 1;
        const res = await this.fetchFiveStarPoems(page);
        if(res) {
          this.fiveStarPage = page;
        }
      }
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

    _fetchFiveStarPoems: async function(page = 1) {
      const url = `/api/v1/me/five-star-poems/${page}`;
      try {
        const resp = await axios.get(url);
        if(resp.code !== 0) {
          return [];
        }
        return resp.data;
      } catch (e) { console.error(e); }
    },
    fetchFiveStarPoems: async function(page = 1) {
      this.fiveStarLoading = true;
      const res = await this._fetchFiveStarPoems(page);
      this.fiveStarLoading = false;
      if(!res || !res.data) return;
      if(page === 1) this.fiveStarPoems = res.data; else this.fiveStarPoems = this.fiveStarPoems.concat(res.data);
      this.fiveStarStop = !res.has_more_pages;
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

// global scroll listener for infinite loading
document.addEventListener('DOMContentLoaded', () => {
  const app = document.getElementById('app');
  if(!app) return;
  app.addEventListener('scroll', () => {
    if(app.__vue__ && app.__vue__.onScroll) {
      app.__vue__.onScroll();
    }
  }, { passive: true });
});
