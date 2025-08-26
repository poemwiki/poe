<template>
  <component :is="tag" v-if="visible" :class="containerClass">
    <span class="loading-box"></span>
  </component>
</template>

<script>
export default {
  name: 'LoadingBox',
  props: {
    visible: { type: Boolean, default: false },
    mode: { type: String, default: 'tail' }, // 'tail' | 'center'
    tag: { type: String, default: 'li' }
  },
  computed: {
    containerClass() {
      if (this.mode === 'center') {
        return 'flex justify-center items-center py-10 relative';
      }
      return 'w-full text-center self-center py-4';
    }
  }
}
</script>

<!-- No scoped styles: rely on global .loading-box animation from common.scss -->
