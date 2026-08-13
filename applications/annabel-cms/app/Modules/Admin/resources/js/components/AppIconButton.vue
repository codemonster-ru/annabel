<script setup>
import { CmButton } from '@codemonster-ru/ui-vue';
import { VueIconify } from '@codemonster-ru/vueforge-icons';

defineProps({
  icon: {
    type: String,
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
    <VueIconify :icon="icon" aria-hidden="true" />
  </CmButton>
</template>
