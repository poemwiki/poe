import '../bootstrap';
import LoadingBox from "../components/LoadingBox.vue";

new Vue({
  el: "#app",
  components: { LoadingBox },
  data: {
    firstLoaded: false,
    contributions: {},
    campaigns: [],
    currentPage: 1,
    stopFetchMore: false,
    loading: false,
    userID: "",
  },

  async mounted() {
    this.campaigns = (await this.fetchCampaigns()).data;
  },

  watch: {},

  methods: {
    onScroll: async function () {
      if (this.stopFetchMore) {
        return;
      }

      const bottomOfWindow =
        this.$refs.page.parentElement.scrollTop + window.innerHeight * 1.7 >
        this.$refs.page.offsetHeight;
      if (!bottomOfWindow) {
        return;
      }

      this.stopFetchMore = true;
      const res = await this.fetchCampaigns(this.currentPage + 1);
      this.campaigns = this.campaigns.concat(res.data);

      this.currentPage++;
      // rate limit
      setTimeout(() => {
        this.stopFetchMore = !res.has_more_pages;
      }, 1000);
    },

    _fetchCampaigns: async function (page = 1) {
      const url = `/api/v1/campaign/list/${page}`;
      try {
        const resp = await axios.get(url);

        if (resp.code !== 0) {
          return [];
        }

        return resp.data;
      } catch (error) {
        console.error(error);
      }
    },

    fetchCampaigns: async function (page = 1) {
      this.loading = true;
      const resp = await this._fetchCampaigns(page);
      this.loading = false;
      return resp;
    },
  },
});
