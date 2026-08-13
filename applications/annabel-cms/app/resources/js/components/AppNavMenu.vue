<script setup>
import { ref, watch } from 'vue';
import AppNavMenuItem from './AppNavMenuItem.vue';

const props = defineProps({
  items: {
    type: Array,
    required: true,
  },
  modelValue: {
    type: String,
    default: '',
  },
  ariaLabel: {
    type: String,
    default: 'Navigation',
  },
  expandMode: {
    type: String,
    default: 'multiple',
  },
  variant: {
    type: String,
    default: 'default',
  },
  compact: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue', 'change', 'select']);
const expandedValues = ref([]);

watch(
  () => [props.items, props.modelValue],
  () => {
    expandedValues.value = Array.from(new Set([
      ...expandedValues.value,
      ...findParentValues(props.items, props.modelValue),
    ]));
  },
  { immediate: true, deep: true },
);

function findParentValues(items, value, parents = []) {
  for (const item of items) {
    if (item.value === value) {
      return parents;
    }

    const nested = findParentValues(item.children || [], value, [...parents, item.value]);

    if (nested.length > 0) {
      return nested;
    }
  }

  return [];
}

function select(item) {
  emit('update:modelValue', item.value);
  emit('change', item.value);
  emit('select', item);
}

function toggle(value) {
  if (expandedValues.value.includes(value)) {
    expandedValues.value = expandedValues.value.filter((entry) => entry !== value);
    return;
  }

  if (props.expandMode === 'single') {
    expandedValues.value = [value];
    return;
  }

  expandedValues.value = [...expandedValues.value, value];
}
</script>

<template>
  <nav
    class="app-nav-menu"
    :class="[`app-nav-menu--${variant}`, { 'app-nav-menu--compact': compact }]"
    :aria-label="ariaLabel"
  >
    <ul class="app-nav-menu__list">
      <AppNavMenuItem
        v-for="item in items"
        :key="item.value"
        :item="item"
        :level="0"
        :active-value="modelValue"
        :expanded-values="expandedValues"
        :compact="compact"
        @select="select"
        @toggle="toggle"
      />
    </ul>
  </nav>
</template>

<style>
.app-nav-menu,
.app-nav-menu__list {
  display: grid;
  gap: var(--cm-space-1);
  min-inline-size: 0;
  margin: 0;
  padding: 0;
  list-style: none;
}

.app-nav-menu__list--nested {
  margin-block-start: var(--cm-space-1);
  margin-inline-start: var(--cm-space-4);
  padding-inline-start: var(--cm-space-2);
  border-inline-start: var(--cm-border-width) solid var(--cm-color-border-divider);
}

.app-nav-menu__group {
  padding: var(--cm-space-2) var(--cm-space-3);
  color: var(--cm-color-text-muted);
  font-size: var(--cm-font-size-sm);
  font-weight: var(--cm-font-weight-semibold);
  text-transform: uppercase;
}

.app-nav-menu__item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--cm-space-2);
  inline-size: 100%;
  min-inline-size: 0;
  min-block-size: 2.25rem;
  padding: var(--cm-space-2) var(--cm-space-3);
  border: var(--cm-border-width) solid transparent;
  border-radius: var(--cm-radius-control);
  background: transparent;
  color: var(--cm-color-text-primary);
  font: inherit;
  text-align: start;
  text-decoration: none;
  cursor: pointer;
  transition:
    background-color var(--cm-motion-duration-normal) var(--cm-motion-ease-standard),
    color var(--cm-motion-duration-normal) var(--cm-motion-ease-standard);
}

.app-nav-menu__item:hover:not(:disabled, .app-nav-menu__item--disabled) {
  background: var(--cm-color-background-surface-hover);
}

.app-nav-menu__item:focus-visible {
  outline: 2px solid var(--cm-color-border-interactive);
  outline-offset: 2px;
}

.app-nav-menu__item--active,
.app-nav-menu__item--ancestor-active {
  background: var(--cm-color-interactive-primary-subtle-background);
  color: var(--cm-color-interactive-primary-subtle-foreground);
  font-weight: var(--cm-font-weight-semibold);
}

.app-nav-menu__item:is(:disabled, .app-nav-menu__item--disabled) {
  color: var(--cm-color-text-disabled);
  cursor: not-allowed;
}

.app-nav-menu__item-content {
  display: flex;
  align-items: center;
  gap: var(--cm-space-2);
  min-inline-size: 0;
}

.app-nav-menu__leading-icon,
.app-nav-menu__chevron,
.app-nav-menu__external-icon {
  inline-size: 1rem;
  block-size: 1rem;
  flex: 0 0 auto;
}

.app-nav-menu__label {
  min-inline-size: 0;
  overflow-wrap: anywhere;
}

.app-nav-menu__chevron {
  transition: transform var(--cm-motion-duration-normal) var(--cm-motion-ease-standard);
}

.app-nav-menu__chevron--open {
  transform: rotate(180deg);
}

.app-nav-menu--compact > .app-nav-menu__list > .app-nav-menu__node > .app-nav-menu__item {
  justify-content: center;
  padding-inline: var(--cm-space-2);
}

.app-nav-menu--compact > .app-nav-menu__list > .app-nav-menu__node > .app-nav-menu__item .app-nav-menu__label,
.app-nav-menu--compact > .app-nav-menu__list > .app-nav-menu__node > .app-nav-menu__item > .app-nav-menu__chevron,
.app-nav-menu--compact > .app-nav-menu__list > .app-nav-menu__node > .app-nav-menu__list--nested {
  display: none;
}

@media (prefers-reduced-motion: reduce) {
  .app-nav-menu__item,
  .app-nav-menu__chevron {
    transition: none;
  }
}
</style>
