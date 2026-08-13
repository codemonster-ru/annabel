<script setup>
import AppProgressBar from './AppProgressBar.vue';

defineProps({
  installing: {
    type: Boolean,
    default: false,
  },
  installed: {
    type: Boolean,
    default: false,
  },
});
</script>

<template>
  <div class="setup-step">
    <template v-if="installing">
      <p class="setup-muted">
        Installing Annabel...
      </p>
      <AppProgressBar indeterminate label="Installation progress" />
    </template>

    <template v-else-if="installed">
      <p class="setup-muted">
        Annabel has been installed successfully.
      </p>

      <AppProgressBar :value="100" label="Installation complete" />
    </template>

    <p v-else class="setup-muted">
      Everything is ready for installation. To continue, click Install.
    </p>
  </div>
</template>
