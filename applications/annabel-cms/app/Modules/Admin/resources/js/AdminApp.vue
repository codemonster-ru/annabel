<script setup>
import { computed } from 'vue';
import { VfThemeProvider } from '@codemonster-ru/vueforge-core';
import LoginScreen from './screens/LoginScreen.vue';
import ForgotPasswordScreen from './screens/ForgotPasswordScreen.vue';
import ResetPasswordScreen from './screens/ResetPasswordScreen.vue';
import AdminShellScreen from './screens/AdminShellScreen.vue';
import { resolveAdminScreen } from './adminFeatures';

const props = defineProps({
  boot: {
    type: Object,
    default: () => ({}),
  },
});

const authenticated = computed(() => Boolean(props.boot.authenticated));
const screen = computed(() => props.boot.screen || 'login');
const csrfToken = computed(() => props.boot.csrfToken || '');
const user = computed(() => props.boot.user || null);
const navigation = computed(() => props.boot.navigation || []);
const navigationValue = computed(() => props.boot.navigationValue || '');
const resetToken = computed(() => props.boot.resetToken || '');
const screenComponent = computed(() => resolveAdminScreen(screen.value));
const screenError = computed(() => (
  authenticated.value && screen.value !== 'dashboard' && !screenComponent.value
    ? screen.value
    : ''
));
</script>

<template>
  <VfThemeProvider>
    <LoginScreen
      v-if="!authenticated && screen === 'login'"
      :csrf-token="csrfToken"
    />
    <ForgotPasswordScreen
      v-else-if="!authenticated && screen === 'forgot-password'"
      :csrf-token="csrfToken"
    />
    <ResetPasswordScreen
      v-else-if="!authenticated && screen === 'reset-password'"
      :csrf-token="csrfToken"
      :reset-token="resetToken"
    />
    <AdminShellScreen
      v-else
      :csrf-token="csrfToken"
      :navigation="navigation"
      :navigation-value="navigationValue"
      :screen-component="screenComponent"
      :screen-error="screenError"
      :user="user"
    />
  </VfThemeProvider>
</template>
