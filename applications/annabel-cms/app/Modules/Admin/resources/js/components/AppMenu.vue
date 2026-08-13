<script setup>
function move(event) {
  if (!['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) return;
  const items = [...event.currentTarget.querySelectorAll('[data-app-menu-item]:not([disabled]):not([aria-disabled="true"])')];
  const index = items.indexOf(event.target);
  if (index < 0) return;
  event.preventDefault();
  const last = items.length - 1;
  const next = event.key === 'Home' ? 0 : event.key === 'End' ? last : event.key === 'ArrowDown' ? (index + 1) % items.length : (index - 1 + items.length) % items.length;
  items[next]?.focus();
}
</script>

<template>
  <div class="app-menu" role="menu" @keydown="move">
    <slot />
  </div>
</template>
