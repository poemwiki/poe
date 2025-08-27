import "../bootstrap";
import CalendarHeat from "./CalendarHeat.vue";
import LoadingBox from "../components/LoadingBox.vue";

new Vue({
  el: "#app",
  components: { CalendarHeat, LoadingBox },
  data: {
    contributions: {},
    originalPoems: [],
    currentPage: 1, // original poems page
    stopFetchMorePoems: false, // original poems reached end
    userID: "",
    originalPoemsTotal: null,
    loading: true,
    // five star poems
    fiveStarPoems: [],
    fiveStarPage: 1,
    fiveStarStop: false,
    fiveStarLoading: true,
    fiveStarPoemsTotal: null,
    // contribution
    contributionTotal: null,
    contributionLoading: false,
    contributionChartData: [], // [[date, count], ...]
    // ui state
    activeTab: "original",
  },
  async mounted() {
    // safer ref access with fallback to global variable injected by backend (optional)
    this.userID = (this.$refs.userID && this.$refs.userID.textContent) || "";
    // eager load all tabs data in parallel (original, five-star, contribution)
    await Promise.all([
      this.loadOriginalFirstPage(),
      this.fetchFiveStarPoems()
    ]);
  },

  watch: {},

  methods: {
    switchTab(tab) {
      if (this.activeTab === tab) return;
      this.activeTab = tab;

      if (tab === "contribution" && this.contributionTotal === null) {
        this.loadContribution();
      }
    },

    onScroll: async function () {
      const container = document.getElementById("app");
      if (!container) return;
      const nearBottom =
        container.scrollTop + container.clientHeight >
        container.scrollHeight - 300;
      if (!nearBottom) return;

      // only load more for active tab to reduce noise
      if (this.activeTab === "original" && !this.stopFetchMorePoems) {
        this.stopFetchMorePoems = true;
        const res = await this.fetchPoems(this.currentPage + 1);
        this.originalPoems = this.originalPoems.concat(res.data);
        this.currentPage++;
        setTimeout(() => {
          this.stopFetchMorePoems = !res.has_more_pages;
        }, 800);
      }
      // five star poems infinite load (only when its tab active)
      if (
        this.activeTab === "fiveStar" &&
        !this.fiveStarStop &&
        !this.fiveStarLoading
      ) {
        const page = this.fiveStarPage + 1;
        const res = await this.fetchFiveStarPoems(page);
        if (res) {
          this.fiveStarPage = page;
        }
      }
    },

    loadOriginalFirstPage: async function () {
      const response = await this.fetchPoems();
      this.originalPoems = response.data;
      this.originalPoemsTotal = response.total;
    },

    _fetchPoems: async function (page = 1) {
      const url = `/api/v1/poem/user/${this.userID}/${page}/10`;
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

    fetchPoems: async function (page = 1) {
      this.loading = true;
      const res = await this._fetchPoems(page);
      this.loading = false;
      return res;
    },

    _fetchFiveStarPoems: async function (page = 1) {
      const url = `/api/v1/me/five-star-poems/${page}`;
      try {
        const resp = await axios.get(url);
        if (resp.code !== 0) {
          return [];
        }
        return resp.data;
      } catch (e) {
        console.error(e);
      }
    },
    fetchFiveStarPoems: async function (page = 1) {
      this.fiveStarLoading = true;
      const res = await this._fetchFiveStarPoems(page);
      this.fiveStarLoading = false;
      if (!res || !res.data) return;
      if (page === 1) {
        this.fiveStarPoems = res.data;
        this.fiveStarPoemsTotal = res.total;
      } else {
        this.fiveStarPoems = this.fiveStarPoems.concat(res.data);
      }
      this.fiveStarStop = !res.has_more_pages;
      return res;
    },

    getContributionData: async function (userID) {
      // If already loaded or currently loading return cached map (avoid refetch loop)
      if (this.contributionTotal !== null || this.contributionLoading) {
        return Object.keys(this.contributions).map((key) => [
          key,
          this.contributions[key].length,
        ]);
      }
      return this.fetchContributions(userID);
    },

    loadContribution: async function () {
      if (this.contributionLoading || this.contributionTotal !== null) return;
      this.contributionLoading = true;
      const data = await this.fetchContributions(this.userID);
      this.contributionChartData = data;
    },

    fetchContributions: async function (userID) {
      this.contributionLoading = true;
      const endDay = new Date();
      const endDateString = new Date().toISOString().slice(0, 10);
      // a year ago
      const startDay = new Date(new Date().setDate(endDay.getDate() - 365));
      const startDateString = startDay.toISOString().slice(0, 10);

      const url = `/api/v1/contribution?user=${userID}&date-from=${startDateString}&date-to=${endDateString}`;

      try {
        const resp = await axios.get(url);

        if (resp.code !== 0) {
          this.contributions = [];
        }

        this.contributions = resp.data;
      } catch (error) {
        this.contributions = { "2022-1-1": [] };
        console.error(error);
      }

      // from startDay to endDay, if this.contributions[date] doesn't exist,
      // set this.contributions[date] with an empty array
      for (
        let d = new Date(endDay.getTime());
        d >= startDay;
        d.setDate(d.getDate() - 1)
      ) {
        const date = d.toISOString().slice(0, 10);
        if (!this.contributions[date]) {
          this.contributions[date] = [];
        }
      }

      const mapArr = Object.keys(this.contributions).map((key) => [
        key,
        this.contributions[key].length,
      ]);
      // compute total contributions across year
      this.contributionTotal = mapArr.reduce((a, b) => a + b[1], 0);
      this.contributionLoading = false;
      return mapArr;
    },
  },
});

// global scroll listener for infinite loading
document.addEventListener("DOMContentLoaded", () => {
  const app = document.getElementById("app");
  if (!app) return;
  app.addEventListener(
    "scroll",
    () => {
      if (app.__vue__ && app.__vue__.onScroll) {
        app.__vue__.onScroll();
      }
    },
    { passive: true }
  );
});
