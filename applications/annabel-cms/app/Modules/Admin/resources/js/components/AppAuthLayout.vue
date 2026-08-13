<script setup>
import { CmContainer } from '@codemonster-ru/ui-vue';

defineProps({
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: '',
  },
});
</script>

<template>
  <main class="app-auth-layout">
    <CmContainer size="md" class="app-auth-layout__container">
      <section class="app-auth-layout__panel">
        <header class="app-auth-layout__header">
          <h1 class="app-auth-layout__title">{{ title }}</h1>
          <p v-if="description" class="app-auth-layout__description">{{ description }}</p>
        </header>
        <div class="app-auth-layout__body">
          <slot />
        </div>
        <footer v-if="$slots.footer" class="app-auth-layout__footer">
          <slot name="footer" />
        </footer>
      </section>
    </CmContainer>
  </main>
</template>
