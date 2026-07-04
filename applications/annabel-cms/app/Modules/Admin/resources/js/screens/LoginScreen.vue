<script setup>
import { ref } from 'vue';
import { VfAlert } from '@codemonster-ru/vueforge-core/alert';
import { VfButton } from '@codemonster-ru/vueforge-core/button';
import { VfCheckbox } from '@codemonster-ru/vueforge-core/checkbox';
import { VfInput } from '@codemonster-ru/vueforge-core/input';
import { VfLink } from '@codemonster-ru/vueforge-core/link';
import { VfThemeSwitch } from '@codemonster-ru/vueforge-core/theme-switch';
import { VfAuthLayout } from '@codemonster-ru/vueforge-layouts/auth-layout';
import AuthFooter from '../components/AuthFooter.vue';
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

async function login() {
  error.value = '';
  errors.value = {};
  loading.value = true;

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
        errors.value = payload.errors || {};
        return;
      }

      error.value = payload.message || 'Sign in failed';
      return;
    }

    window.location.assign('/admin');
  } catch (e) {
    error.value = 'Unable to sign in. Please try again.';
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <VfAuthLayout
    class="admin-auth-layout"
    title="Annabel CMS"
    description="Sign in to your admin panel"
  >
    <VfThemeSwitch class="auth-panel__theme" variant="switch" size="sm" />

    <form class="auth-form" method="post" action="/admin/login" novalidate @submit.prevent="login">
      <VfAlert v-if="error" tone="danger" title="Sign in failed">
        {{ error }}
      </VfAlert>

      <div class="field">
        <label for="login">Username or email</label>
        <VfInput
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
        <VfInput
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
        <VfCheckbox class="auth-form__remember" v-model="remember" label="Remember me?" />
        <VfLink href="/admin/forgot-password" underline="none">
          Forgot password?
        </VfLink>
      </div>

      <VfButton type="submit" :disabled="loading" block>
        {{ loading ? 'Signing in...' : 'Sign in' }}
      </VfButton>
    </form>

    <template #footer>
      <AuthFooter />
    </template>
  </VfAuthLayout>
</template>
