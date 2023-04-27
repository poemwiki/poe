import '../bootstrap';

new Vue({
  el: '#app',
  data: {
    firstLoaded: false,
    contributions: {},
    campaigns: [],
    currentPage: 1,
    stopFetchMore: false,
    loading: false,
    userID: '',
  },

  async mounted() {
    const response = await this.fetchCampaigns();
    console.log(response.data);
    this.campaigns = response.data;
  },

  watch: {
  },

  methods: {
    onScroll: async function() {
      console.log('onScroll');
      if (this.stopFetchMore) {
        return
      }

      const bottomOfWindow =
        this.$refs.page.parentElement.scrollTop + window.innerHeight * 1.7 > this.$refs.page.offsetHeight;
      if(!bottomOfWindow) {
        return;
      }

      this.stopFetchMore = true;
      const res = await this.fetchCampaigns(this.currentPage+1);
      this.campaigns = this.campaigns.concat(res.data);

      this.currentPage++;
      // rate limit
      setTimeout(() => {
        this.stopFetchMore = !res.has_more_pages;
      }, 1000);
    },

    fetchCampaigns: async function(page = 1) {
      this.loading = true;
      const url = `/api/v1/campaign/list/${page}`;
      try {
        const data = await axios.get(url);
        this.loading = false;
        return data;
      } catch (error) {
        this.loading = false;
        console.error(error);
      }
    },

  }
});
