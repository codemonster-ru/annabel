<script setup>
import { computed } from 'vue';
import { VfThemeProvider } from '@codemonster-ru/vueforge-core';
import LoginScreen from './screens/LoginScreen.vue';
import ForgotPasswordScreen from './screens/ForgotPasswordScreen.vue';
import ResetPasswordScreen from './screens/ResetPasswordScreen.vue';
import DashboardScreen from './screens/DashboardScreen.vue';

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
const modules = computed(() => props.boot.modules || {});
const resetToken = computed(() => props.boot.resetToken || '');
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
    <DashboardScreen
      v-else
      :csrf-token="csrfToken"
      :user="user"
      :modules="modules"
    />
  </VfThemeProvider>
</template>
