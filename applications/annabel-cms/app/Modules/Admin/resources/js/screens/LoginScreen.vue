<script setup>
import { ref } from 'vue';
import { CmAlert } from '@codemonster-ru/ui-vue';
import { CmButton } from '@codemonster-ru/ui-vue';
import { CmCheckbox } from '@codemonster-ru/ui-vue';
import { CmInput } from '@codemonster-ru/ui-vue';
import { CmLink } from '@codemonster-ru/ui-vue';
import AppThemeSwitch from '../components/AppThemeSwitch.vue';
import AuthFooter from '../components/AuthFooter.vue';
import AppAuthLayout from '../components/AppAuthLayout.vue';
import { firstError } from '../support/errors';

const props = defineProps({
  csrfToken: {
    type: String,
    required: true,
  },
});

const loginValue = ref('');
const password = ref('');
const remember = ref(false);
const error = ref('');
const errors = ref({});
const loading = ref(false);
const minimumLoaderDuration = 500;

async function login() {
  if (loading.value) {
    return;
  }

  loading.value = true;
  const loaderStartedAt = Date.now();

  const body = new FormData();
  body.append('_token', props.csrfToken);
  body.append('login', loginValue.value);
  body.append('password', password.value);
  body.append('remember', remember.value ? '1' : '0');

  try {
    const response = await fetch('/admin/login', {
      method: 'POST',
      headers: { Accept: 'application/json' },
      body,
      credentials: 'same-origin',
    });
    const payload = await response.json();

    if (!response.ok) {
      if (response.status === 422) {
        error.value = '';
        errors.value = payload.errors || {};
        return;
      }

      errors.value = {};

      if (response.status === 429) {
        const retryAfter = Number.parseInt(response.headers.get('Retry-After') || '', 10);
        error.value = Number.isFinite(retryAfter) && retryAfter > 0
          ? `Too many sign-in attempts. Please try again in ${retryAfter} seconds.`
          : 'Too many sign-in attempts. Please try again later.';
        return;
      }

      error.value = payload.message || 'Sign in failed';
      return;
    }

    window.location.assign('/admin');
  } catch (e) {
    error.value = 'Unable to sign in. Please try again.';
  } finally {
    const remainingDuration = minimumLoaderDuration - (Date.now() - loaderStartedAt);

    if (remainingDuration > 0) {
      await new Promise((resolve) => window.setTimeout(resolve, remainingDuration));
    }

    loading.value = false;
  }
}

function submitOnEnter(event) {
  if (event.isComposing || !(event.target instanceof HTMLInputElement)) {
    return;
  }

  event.preventDefault();
  login();
}
</script>

<template>
  <AppAuthLayout
    class="admin-auth-layout"
    title="Annabel"
    description="Sign in to your admin panel"
  >
    <AppThemeSwitch class="auth-panel__theme" variant="switch" size="sm" />

    <form class="auth-form" method="post" action="/admin/login" novalidate @submit.prevent="login" @keydown.enter="submitOnEnter">
      <CmAlert v-if="error" tone="danger" title="Sign in failed">
        {{ error }}
      </CmAlert>

      <div class="field">
        <label for="login">Username or email</label>
        <CmInput
          id="login"
          v-model="loginValue"
          type="text"
          autocomplete="username"
          :invalid="Boolean(firstError(errors, 'login'))"
        />
        <p v-if="firstError(errors, 'login')" class="field__error">
          {{ firstError(errors, 'login') }}
        </p>
      </div>

      <div class="field">
        <label for="password">Password</label>
        <CmInput
          id="password"
          v-model="password"
          type="password"
          autocomplete="current-password"
          password-reveal
          :invalid="Boolean(firstError(errors, 'password'))"
        />
        <p v-if="firstError(errors, 'password')" class="field__error">
          {{ firstError(errors, 'password') }}
        </p>
      </div>

      <div class="auth-form__actions">
        <CmCheckbox class="auth-form__remember" v-model="remember" label="Remember me?" />
        <CmLink href="/admin/forgot-password" underline="none">
          Forgot password?
        </CmLink>
      </div>

      <CmButton type="submit" :loading="loading" block>
        {{ loading ? 'Signing in...' : 'Sign in' }}
      </CmButton>
    </form>

    <template #footer>
      <AuthFooter />
    </template>
  </AppAuthLayout>
</template>
