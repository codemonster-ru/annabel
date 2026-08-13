<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
});
</script>

<template>
  <header class="app-page-header">
    <div class="app-page-header__heading">
      <h1 class="app-page-header__title">{{ title }}</h1>
      <div v-if="$slots.description" class="app-page-header__description">
        <slot name="description" />
      </div>
    </div>
    <div v-if="$slots.actions" class="app-page-header__actions">
      <slot name="actions" />
    </div>
  </header>
</template>
