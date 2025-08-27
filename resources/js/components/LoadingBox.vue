<template>
  <component :is="tag" :class="containerClass">
    <div class="loading-box"></div>
  </component>
</template>

<script>
export default {
  name: 'LoadingBox',
  props: {
    tag: { type: String, default: 'div' },
    className: { type: String, default: '' }
  },
  computed: {
    containerClass() {
      return 'w-full h-full flex justify-center items-center py-10 relative ' + this.className;
    }
  }
}
</script>

<!-- No scoped styles: rely on global .loading-box animation from common.scss -->
