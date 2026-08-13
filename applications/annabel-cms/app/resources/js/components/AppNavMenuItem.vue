<script setup>
import { computed } from 'vue';
import { icons, VueIconify } from '@codemonster-ru/vueforge-icons';

defineOptions({ name: 'AppNavMenuItem' });

const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
  level: {
    type: Number,
    required: true,
  },
  activeValue: {
    type: String,
    default: '',
  },
  expandedValues: {
    type: Array,
    required: true,
  },
  compact: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['select', 'toggle']);

const hasChildren = computed(() => Boolean(props.item.children?.length));
const isActive = computed(() => props.item.value === props.activeValue);
const isAncestorActive = computed(() => containsValue(props.item.children || [], props.activeValue));
const isExpanded = computed(() => props.expandedValues.includes(props.item.value));
const linkRel = computed(() => (
  props.item.rel || (props.item.target === '_blank' ? 'noopener noreferrer' : undefined)
));

function containsValue(items, value) {
  return Boolean(value) && items.some((item) => (
    item.value === value || containsValue(item.children || [], value)
  ));
}

function select(event) {
  if (props.item.disabled) {
    event?.preventDefault();
    return;
  }

  emit('select', props.item);
}

function toggle() {
  if (!props.item.disabled) {
    emit('toggle', props.item.value);
  }
}
</script>

<template>
  <li
    class="app-nav-menu__node"
    :class="{
      'app-nav-menu__node--active': isActive,
      'app-nav-menu__node--ancestor-active': isAncestorActive,
    }"
  >
    <div v-if="item.kind === 'group'" class="app-nav-menu__group">
      {{ item.label }}
    </div>

    <button
      v-else-if="hasChildren"
      class="app-nav-menu__item app-nav-menu__item--branch"
      :class="{ 'app-nav-menu__item--ancestor-active': isAncestorActive }"
      type="button"
      :disabled="item.disabled"
      :aria-expanded="isExpanded"
      :title="compact && level === 0 ? item.label : undefined"
      @click="toggle"
    >
      <span class="app-nav-menu__item-content">
        <VueIconify
          v-if="item.leadingIcon"
          class="app-nav-menu__leading-icon"
          :icon="item.leadingIcon"
          aria-hidden="true"
        />
        <span class="app-nav-menu__label">{{ item.label }}</span>
      </span>
      <VueIconify
        class="app-nav-menu__chevron"
        :class="{ 'app-nav-menu__chevron--open': isExpanded }"
        :icon="icons.chevronDown"
        aria-hidden="true"
      />
    </button>

    <a
      v-else-if="item.href"
      class="app-nav-menu__item"
      :class="{ 'app-nav-menu__item--active': isActive, 'app-nav-menu__item--disabled': item.disabled }"
      :href="item.href"
      :target="item.target"
      :rel="linkRel"
      :aria-current="isActive ? 'page' : undefined"
      :aria-disabled="item.disabled || undefined"
      :tabindex="item.disabled ? -1 : undefined"
      :title="compact && level === 0 ? item.label : undefined"
      @click="select"
    >
      <span class="app-nav-menu__item-content">
        <VueIconify
          v-if="item.leadingIcon"
          class="app-nav-menu__leading-icon"
          :icon="item.leadingIcon"
          aria-hidden="true"
        />
        <span class="app-nav-menu__label">{{ item.label }}</span>
      </span>
      <VueIconify
        v-if="item.target === '_blank'"
        class="app-nav-menu__external-icon"
        :icon="icons.externalLink"
        aria-hidden="true"
      />
    </a>

    <button
      v-else
      class="app-nav-menu__item"
      :class="{ 'app-nav-menu__item--active': isActive }"
      type="button"
      :disabled="item.disabled"
      :aria-current="isActive ? 'step' : undefined"
      @click="select"
    >
      <span class="app-nav-menu__item-content">
        <VueIconify
          v-if="item.leadingIcon"
          class="app-nav-menu__leading-icon"
          :icon="item.leadingIcon"
          aria-hidden="true"
        />
        <span class="app-nav-menu__label">{{ item.label }}</span>
      </span>
    </button>

    <ul
      v-if="hasChildren && (item.kind === 'group' || isExpanded)"
      class="app-nav-menu__list app-nav-menu__list--nested"
    >
      <AppNavMenuItem
        v-for="child in item.children"
        :key="child.value"
        :item="child"
        :level="level + 1"
        :active-value="activeValue"
        :expanded-values="expandedValues"
        :compact="compact"
        @select="emit('select', $event)"
        @toggle="emit('toggle', $event)"
      />
    </ul>
  </li>
</template>
