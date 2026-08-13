<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, useId, watch } from 'vue';

const props = defineProps({
  placement: {
    type: String,
    default: 'bottom-start',
  },
});

const root = ref(null);
const triggerContainer = ref(null);
const open = ref(false);
const id = `app-dropdown-${useId().replaceAll(':', '')}`;

function triggerButton() {
  return triggerContainer.value?.querySelector('button');
}

function synchronizeTrigger() {
  const button = triggerButton();
  if (!button) return;
  button.setAttribute('aria-haspopup', 'menu');
  button.setAttribute('aria-controls', `${id}-menu`);
  button.setAttribute('aria-expanded', String(open.value));
}

function setOpen(value, restoreFocus = false) {
  open.value = value;
  if (restoreFocus) triggerButton()?.focus();
}

function onKeydown(event) {
  if (event.key === 'Escape') {
    event.preventDefault();
    setOpen(false, true);
    return;
  }
  if (!['ArrowDown', 'ArrowUp'].includes(event.key)) return;
  event.preventDefault();
  setOpen(true);
  void nextTick(() => {
    const items = root.value?.querySelectorAll('[data-app-menu-item]:not([disabled]):not([aria-disabled="true"])');
    (event.key === 'ArrowUp' ? items?.[items.length - 1] : items?.[0])?.focus();
  });
}

function onDocumentClick(event) {
  if (!root.value?.contains(event.target)) setOpen(false);
}

watch(open, synchronizeTrigger);
onMounted(() => {
  synchronizeTrigger();
  document.addEventListener('click', onDocumentClick);
});
onBeforeUnmount(() => document.removeEventListener('click', onDocumentClick));
</script>

<template>
  <div
    ref="root"
    :class="['app-dropdown', `app-dropdown--${props.placement}`, { 'app-dropdown--open': open }]"
    @keydown="onKeydown"
    @app-menu-select="setOpen(false, true)"
  >
    <div ref="triggerContainer" class="app-dropdown__trigger" @click="setOpen(!open)">
      <slot name="trigger" />
    </div>
    <div :id="`${id}-menu`" class="app-dropdown__menu" :hidden="!open">
      <slot />
    </div>
  </div>
</template>
