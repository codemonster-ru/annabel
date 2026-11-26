<script setup>
import { CmIcon } from '@codemonster-ru/ui-vue';

const props = defineProps({
  label: {
    type: String,
    required: true,
  },
  icon: {
    type: Object,
    default: null,
  },
  href: {
    type: String,
    default: '',
  },
  tone: {
    type: String,
    default: 'default',
  },
  disabled: Boolean,
});

const emit = defineEmits(['select']);

function select(event) {
  if (props.disabled) {
    event.preventDefault();
    return;
  }
  emit('select');
  event.currentTarget.dispatchEvent(new CustomEvent('app-menu-select', { bubbles: true }));
}
</script>

<template>
  <component
    :is="href ? 'a' : 'button'"
    :class="['app-menu__item', { 'app-menu__item--danger': tone === 'danger' }]"
    :type="href ? undefined : 'button'"
    :href="href && !disabled ? href : undefined"
    role="menuitem"
    :disabled="!href && disabled ? true : undefined"
    :aria-disabled="href && disabled ? 'true' : undefined"
    data-app-menu-item
    @click="select"
  >
    <CmIcon v-if="icon" :icon="icon" aria-hidden="true" />
    <span>{{ label }}</span>
  </component>
</template>
