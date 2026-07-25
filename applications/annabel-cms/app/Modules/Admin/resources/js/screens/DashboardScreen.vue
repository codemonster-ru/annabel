<script setup>
import { VfCard } from '@codemonster-ru/vueforge-core/card';
</script>

<template>
  <VfCard>
    <div class="dashboard-welcome">
      <h2>Welcome to Annabel</h2>
      <p>Choose a section from the navigation to manage your site.</p>
    </div>
  </VfCard>
</template>

<style scoped>
.dashboard-welcome {
  display: grid;
  gap: var(--vf-surface-gap-compact);
}

.dashboard-welcome h2,
.dashboard-welcome p {
  margin: 0;
}

.dashboard-welcome p {
  color: var(--vf-color-muted);
}
</style>
