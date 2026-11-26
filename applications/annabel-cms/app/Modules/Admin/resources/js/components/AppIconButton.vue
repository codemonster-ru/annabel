<script setup>
import { CmButton, CmIcon } from '@codemonster-ru/ui-vue';

defineProps({
  icon: {
    type: Object,
    required: true,
  },
  variant: {
    type: String,
    default: 'ghost',
  },
  size: {
    type: String,
    default: 'md',
  },
});
</script>

<template>
  <CmButton class="app-icon-button" :variant="variant" :size="size">
    <CmIcon :icon="icon" aria-hidden="true" />
  </CmButton>
</template>
