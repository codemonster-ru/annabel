<script setup>
import { computed, inject } from 'vue';
import { CmButton, CmSwitch } from '@codemonster-ru/ui-vue';
import { icons, VueIconify } from '@codemonster-ru/vueforge-icons';
import { appThemeKey } from './appTheme';

const props = defineProps({
  variant: {
    type: String,
    default: 'switch',
    validator: value => ['switch', 'button'].includes(value),
  },
  buttonVariant: {
    type: String,
    default: 'secondary',
  },
  size: {
    type: String,
    default: 'md',
  },
});

const theme = inject(appThemeKey);
if (!theme) throw new Error('AppThemeSwitch must be used inside AppThemeProvider.');

const checked = computed(() => theme.resolvedTheme.value === 'dark');
const icon = computed(() => (checked.value ? icons.moon : icons.sun));
const label = computed(() => (checked.value ? 'Switch to light theme' : 'Switch to dark theme'));

function updateTheme(value) {
  theme.setTheme(value ? 'dark' : 'light');
}
</script>

<template>
  <CmButton
    v-if="props.variant === 'button'"
    class="app-theme-switch"
    :variant="props.buttonVariant"
    :size="props.size"
    :aria-label="label"
    @click="updateTheme(!checked)"
  >
    <VueIconify :icon="icon" aria-hidden="true" />
  </CmButton>
  <CmSwitch
    v-else
    class="app-theme-switch"
    :model-value="checked"
    :size="props.size"
    :aria-label="label"
    @update:model-value="updateTheme"
  />
</template>
