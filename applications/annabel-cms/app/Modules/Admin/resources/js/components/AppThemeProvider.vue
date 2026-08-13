<script setup>
import { computed, onBeforeUnmount, onMounted, provide, ref, watch } from 'vue';
import { appThemeKey } from './appTheme';

const props = defineProps({
  defaultTheme: {
    type: String,
    default: 'system',
    validator: value => ['light', 'dark', 'system'].includes(value),
  },
  storageKey: {
    type: String,
    required: true,
  },
});

const mode = ref(props.defaultTheme);
const systemTheme = ref('light');
const mediaQuery = ref(null);
const mounted = ref(false);
const resolvedTheme = computed(() => (mode.value === 'system' ? systemTheme.value : mode.value));

function readStoredTheme() {
  try {
    const value = window.localStorage.getItem(props.storageKey);
    return ['light', 'dark', 'system'].includes(value) ? value : null;
  } catch {
    return null;
  }
}

function updateSystemTheme(event) {
  systemTheme.value = (event?.matches ?? mediaQuery.value?.matches) ? 'dark' : 'light';
}

function setTheme(value) {
  if (['light', 'dark', 'system'].includes(value)) {
    mode.value = value;
  }
}

watch(mode, value => {
  if (!mounted.value) return;

  try {
    window.localStorage.setItem(props.storageKey, value);
  } catch {
    // Storage can be unavailable in privacy-restricted browser contexts.
  }
});

watch(resolvedTheme, value => {
  if (mounted.value) document.documentElement.setAttribute('data-cm-theme', value);
});

onMounted(() => {
  mode.value = readStoredTheme() ?? props.defaultTheme;
  mediaQuery.value = window.matchMedia?.('(prefers-color-scheme: dark)') ?? null;
  updateSystemTheme();
  document.documentElement.setAttribute('data-cm-theme', resolvedTheme.value);
  mediaQuery.value?.addEventListener('change', updateSystemTheme);
  mounted.value = true;
});

onBeforeUnmount(() => {
  mediaQuery.value?.removeEventListener('change', updateSystemTheme);
});

provide(appThemeKey, { resolvedTheme, setTheme });
</script>

<template>
  <slot />
</template>
