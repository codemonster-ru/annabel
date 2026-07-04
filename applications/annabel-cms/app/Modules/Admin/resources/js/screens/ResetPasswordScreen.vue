<script setup>
import { ref } from 'vue';
import { VfAlert } from '@codemonster-ru/vueforge-core/alert';
import { VfButton } from '@codemonster-ru/vueforge-core/button';
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
  resetToken: {
    type: String,
    required: true,
  },
});

const password = ref('');
const passwordConfirmation = ref('');
const error = ref('');
const errors = ref({});
const loading = ref(false);

async function submitResetPassword() {
  error.value = '';
  errors.value = {};
  loading.value = true;

  const body = new FormData();
  body.append('_token', props.csrfToken);
  body.append('token', props.resetToken);
  body.append('password', password.value);
  body.append('password_confirmation', passwordConfirmation.value);

  try {
    const response = await fetch('/admin/reset-password', {
      method: 'POST',
      headers: { Accept: 'application/json' },
      body,
      credentials: 'same-origin',
    });
    const payload = await response.json();

    if (!response.ok) {
      if (response.status === 422) {
        errors.value = payload.errors || {};
        error.value = firstError(errors, 'token');
        return;
      }

      error.value = payload.message || 'Unable to reset password.';
      return;
    }

    window.location.assign(payload.redirect || '/admin/login');
  } catch (e) {
    error.value = 'Unable to reset password. Please try again.';
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <VfAuthLayout
    class="admin-auth-layout"
    title="Choose a new password"
    description="Create a new password for your admin account"
  >
    <VfThemeSwitch class="auth-panel__theme" variant="switch" size="sm" />

    <form class="auth-form" method="post" action="/admin/reset-password" novalidate @submit.prevent="submitResetPassword">
      <VfAlert v-if="error" tone="danger" title="Reset password failed">
        {{ error }}
      </VfAlert>

      <div class="field">
        <label for="new-password">New password</label>
        <VfInput
          id="new-password"
          v-model="password"
          type="password"
          autocomplete="new-password"
          password-reveal
          :invalid="Boolean(firstError(errors, 'password'))"
        />
        <p v-if="firstError(errors, 'password')" class="field__error">
          {{ firstError(errors, 'password') }}
        </p>
      </div>

      <div class="field">
        <label for="new-password-confirmation">Confirm new password</label>
        <VfInput
          id="new-password-confirmation"
          v-model="passwordConfirmation"
          type="password"
          autocomplete="new-password"
          password-reveal
          :invalid="Boolean(firstError(errors, 'password_confirmation'))"
        />
        <p v-if="firstError(errors, 'password_confirmation')" class="field__error">
          {{ firstError(errors, 'password_confirmation') }}
        </p>
      </div>

      <div class="auth-form__back">
        <span class="auth-form__back-label">Back to</span>
        <VfLink href="/admin/login" underline="none">
          sign in
        </VfLink>
      </div>

      <VfButton type="submit" :disabled="loading" block>
        {{ loading ? 'Updating...' : 'Update password' }}
      </VfButton>
    </form>

    <template #footer>
      <AuthFooter />
    </template>
  </VfAuthLayout>
</template>
