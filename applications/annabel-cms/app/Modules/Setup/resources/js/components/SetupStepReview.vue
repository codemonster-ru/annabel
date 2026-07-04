<script setup>
import { VfProgressBar } from '@codemonster-ru/vueforge-core/progress-bar';

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
        Installing Annabel CMS...
      </p>
      <VfProgressBar indeterminate label="Installation progress" />
    </template>

    <template v-else-if="installed">
      <p class="setup-muted">
        Annabel CMS has been installed successfully.
      </p>

      <VfProgressBar :value="100" label="Installation complete" />
    </template>

    <p v-else class="setup-muted">
      Everything is ready for installation. To continue, click Install.
    </p>
  </div>
</template>
