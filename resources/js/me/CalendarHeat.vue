<template>
  <div class="calendar-heatmap" ref="chart"></div>
</template>

<script>
import * as echarts from 'echarts/core';
import {CalendarComponent, TooltipComponent, VisualMapComponent, DataZoomComponent} from 'echarts/components';
import {HeatmapChart} from 'echarts/charts';
import {SVGRenderer} from 'echarts/renderers';

echarts.use([HeatmapChart, CalendarComponent, TooltipComponent, VisualMapComponent, SVGRenderer, DataZoomComponent]);

function yMd(date) {
  return date.toISOString().slice(0, 10);
}

// short for new Date()
function d() {
  return new Date(...arguments);
}

export default {
  name: 'CalendarHeatmap',
  props: {
    data: { // preferred direct data array [[date, count]]
      type: Array,
      default: () => []
    }
  },
  mounted() {
    this.initChart();

    window.addEventListener('resize', () => {
      console.log('resize');
      // this.chart.resize();
    });
  },
  beforeDestroy() {
    this.disposeChart();
  },
  methods: {
    async initChart() {
      const container = this.$refs.chart;
      const containerWidth = container.getBoundingClientRect().width;
      const minCellSize = 12;
      const isNarrowScreen = containerWidth < minCellSize * 52;
      const chartWidth = 'auto';

      const containWeeks = Math.floor(containerWidth / minCellSize);
      const today = d(yMd(d()));
      let minDate = d(d(today).setDate(today.getDate() - containWeeks * 7 - 1));
      // 取 minDate 下个月的第一天
      minDate = d(d(minDate.setMonth(minDate.getMonth() + 1)).setDate(1));
      console.log('minDate', yMd(minDate), minDate);

      this.chart = echarts.init(this.$refs.chart, {
        renderer: 'svg',
        width: chartWidth,
      });

      const endDay = d().getDate();
      const endDateString = d().toISOString().slice(0, 10);
      // a year ago
      const startDay = d(d().setDate(endDay - 365));
      const startDayString = startDay.toISOString().slice(0, 10);

      // obtain raw data
      let rawData = [];
      if (this.data && this.data.length) {
        rawData = this.data;
      }

      const options = {
        tooltip: {
          formatter: function (params) {
            return `${params.value[0]} ${params.value[1]} ${params.value[1] > 1 ? 'contributions' : 'contribution'}`
          },
          padding: [2, 4],
          textStyle: {
            fontSize: 12,
          },
        },
        // TODO Display recent and past activities from left to right.
        // TODO if container width > minCellSize * 52, then the calendar width will be auto
        // otherwise, the calendar width will be minCellSize * 52 (scrollable)
        calendar: {
          left: 10,
          right: 10,
          top: 52,
          width: chartWidth,
          height: 120,
          cellSize: [minCellSize, minCellSize],
          range: [isNarrowScreen ? minDate.toISOString().slice(0, 10) : startDayString, endDateString],
          splitLine: {
            lineStyle: {
              color: '#fff',
              width: 2.3,
              type: 'solid'
            },
            silent: true
          },
          yearLabel: {
            show: false
          },
          monthLabel: {
            show: true,
            borderColor: '#fff',
            align: 'left'
          },
          dayLabel: {
            show: false,
            nameMap: ['日', '一', '二', '三', '四', '五', '六']
          },
          itemStyle: {
            color: '#e8eaed',
            borderColor: '#fff',
            borderWidth: 1
          },
          emphasis: {
            itemStyle: {
              borderColor: '#fff',
              borderWidth: 1
            }
          }
        },
        visualMap: {
          // show: false,
          type: 'piecewise',
          orient: 'horizontal',
          right: 0,
          top: 'top',
          padding: [5, 5, 5, 0],
          text: ['High', 'Low'],
          textStyle: {
            lineHeight: 12,
          },
          itemGap: 4,
          itemWidth: 12,
          itemHeight: 12,
          pieces: [
            {symbol: 'rect', lt: 1, color: '#e8eaed'},
            {symbol: 'rect', gte: 1, lte: 2, color: '#93e5a2'},
            {symbol: 'rect', gte: 3, lte: 4, color: '#3fbb5f'},
            {symbol: 'rect', gte: 5, lte: 8, color: '#30964b'},
            {symbol: 'rect', gt: 8, color: '#226235'},
          ]
        },
        series: [{
          type: 'heatmap',
          coordinateSystem: 'calendar',
          data: (rawData || []).filter(item => item && d(item[0]) >= minDate),
          xAxisIndex: 0,
          calendarIndex: 0,
          emphasis: {
            focus: 'series'
          }
        }],
      };

      this.chart.setOption(options);
      const svg = this.chart.getDom().querySelector('svg');
      // console.log(svg);
      // svg.setAttribute('width', chartWidth);
      const parent = svg.parentNode;
      parent.style.overflowX = 'auto';
    },
    disposeChart() {
      const chart = this.$refs.chart && this.$refs.chart.echartsInstance;
      chart && chart.dispose();
    },
    updateData() {
      if (!this.chart) return;
      // const series = this.chart.getOption().series || [];
      const minCellSize = 12;
      const containerWidth = this.$refs.chart.getBoundingClientRect().width;
      const containWeeks = Math.floor(containerWidth / minCellSize);
      const today = d(yMd(d()));
      let minDate = d(d(today).setDate(today.getDate() - containWeeks * 7 - 1));
      minDate = d(d(minDate.setMonth(minDate.getMonth() + 1)).setDate(1));
      const filtered = (this.data || []).filter(item => item && d(item[0]) >= minDate);
      this.chart.setOption({
        series: [{
          data: filtered
        }]
      });
    }
  },
  watch: {
    data() {
      this.updateData();
    }
  }
};
</script>

<style scoped>
.calendar-heatmap {
  height: 170px;
}
.calendar-heatmap>div:first-child{
  overflow-x: auto!important;
}
</style>
