<script setup>
import { computed } from 'vue';

const props = defineProps({
  labelWidth: {
    type: String,
    default: 'minmax(10rem, 25%)',
  },
});

const style = computed(() => ({ '--app-form-layout-label-width': props.labelWidth }));
</script>

<template>
  <div class="app-form-layout" :style="style">
    <slot />
  </div>
</template>
