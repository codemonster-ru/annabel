<script setup>
import { useAttrs } from 'vue';

defineOptions({ inheritAttrs: false });

defineProps({
  as: {
    type: String,
    default: 'main',
  },
  title: {
    type: String,
    default: '',
  },
  description: {
    type: String,
    default: '',
  },
});

const attrs = useAttrs();
</script>

<template>
  <component :is="as" class="app-setup-layout" v-bind="attrs">
    <div class="app-setup-layout__container">
      <div class="app-setup-layout__panel">
        <div v-if="$slots.brand" class="app-setup-layout__brand">
          <slot name="brand" />
        </div>

        <aside v-if="$slots.aside" class="app-setup-layout__aside">
          <slot name="aside" />
        </aside>

        <header v-if="$slots.title || title || $slots.description || description" class="app-setup-layout__header">
          <h1 v-if="$slots.title || title" class="app-setup-layout__title">
            <slot name="title">{{ title }}</slot>
          </h1>
          <p v-if="$slots.description || description" class="app-setup-layout__description">
            <slot name="description">{{ description }}</slot>
          </p>
        </header>

        <section class="app-setup-layout__main">
          <div class="app-setup-layout__body">
            <slot />
          </div>
          <div v-if="$slots.actions" class="app-setup-layout__actions">
            <slot name="actions" />
          </div>
        </section>

        <footer v-if="$slots.footer" class="app-setup-layout__footer">
          <slot name="footer" />
        </footer>
      </div>
    </div>
  </component>
</template>

<style scoped>
.app-setup-layout {
  display: flex;
  align-items: center;
  inline-size: 100%;
  min-block-size: 100vh;
  min-inline-size: 20rem;
  background: var(--cm-color-background-surface-subtle);
  color: var(--cm-color-text-primary);
}

.app-setup-layout__container {
  inline-size: 100%;
  max-inline-size: 64rem;
  margin-inline: auto;
  padding: var(--cm-space-4);
}

.app-setup-layout__panel {
  display: grid;
  grid-template-areas:
    'brand'
    'header'
    'main'
    'aside'
    'footer';
  grid-template-columns: minmax(0, 1fr);
  gap: var(--cm-space-4);
  padding: var(--cm-space-6);
  border: var(--cm-border-width) solid var(--cm-color-border-default);
  border-radius: var(--cm-radius-surface);
  background: var(--cm-color-background-surface);
  box-shadow: var(--cm-shadow-surface);
}

.app-setup-layout__brand {
  grid-area: brand;
  min-inline-size: 0;
}

.app-setup-layout__aside {
  grid-area: aside;
  min-inline-size: 0;
}

.app-setup-layout__header {
  grid-area: header;
  display: grid;
  align-self: start;
  gap: var(--cm-space-2);
  min-inline-size: 0;
}

.app-setup-layout__title,
.app-setup-layout__description {
  margin: 0;
}

.app-setup-layout__title {
  font-size: var(--cm-font-size-2xl);
  line-height: var(--cm-line-height-tight);
}

.app-setup-layout__description,
.app-setup-layout__footer {
  color: var(--cm-color-text-muted);
  line-height: var(--cm-line-height-normal);
}

.app-setup-layout__main {
  grid-area: main;
  display: flex;
  flex-direction: column;
  gap: var(--cm-space-6);
  min-inline-size: 0;
}

.app-setup-layout__actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: var(--cm-space-4);
  margin-block-start: auto;
}

.app-setup-layout__footer {
  grid-area: footer;
  text-align: center;
}

@media (min-width: 768px) {
  .app-setup-layout__panel:has(.app-setup-layout__aside) {
    position: relative;
    grid-template-areas:
      'brand header'
      'aside main'
      'footer footer';
    grid-template-columns: minmax(0, 16rem) minmax(0, 1fr);
    gap: 0;
    padding: 0;
  }

  .app-setup-layout__panel:has(.app-setup-layout__aside)::before {
    content: '';
    position: absolute;
    inset-block: 0;
    inset-inline-start: 16rem;
    inline-size: var(--cm-border-width);
    background: var(--cm-color-border-divider);
  }

  .app-setup-layout__panel:has(.app-setup-layout__aside) > :where(
    .app-setup-layout__brand,
    .app-setup-layout__aside,
    .app-setup-layout__header,
    .app-setup-layout__main,
    .app-setup-layout__footer
  ) {
    padding: var(--cm-space-8);
  }

  .app-setup-layout__panel:has(.app-setup-layout__aside) > .app-setup-layout__brand,
  .app-setup-layout__panel:has(.app-setup-layout__aside) > .app-setup-layout__header {
    padding-block-end: 0;
  }

  .app-setup-layout__panel:has(.app-setup-layout__aside) > .app-setup-layout__footer {
    border-block-start: var(--cm-border-width) solid var(--cm-color-border-divider);
  }
}

@media (max-width: 479.98px) {
  .app-setup-layout__container {
    padding: 0;
  }

  .app-setup-layout__panel {
    min-block-size: 100vh;
    padding: var(--cm-space-6) var(--cm-space-4);
    border: 0;
    border-radius: 0;
    box-shadow: none;
  }
}
</style>
