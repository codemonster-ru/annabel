<script setup>
import { ref } from 'vue';
import { CmAlert } from '@codemonster-ru/ui-vue';
import { CmButton } from '@codemonster-ru/ui-vue';
import { CmInput } from '@codemonster-ru/ui-vue';
import { CmLink } from '@codemonster-ru/ui-vue';
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
const minimumLoaderDuration = 500;

async function submitResetPassword() {
  if (loading.value) {
    return;
  }

  loading.value = true;
  const loaderStartedAt = Date.now();

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
        error.value = firstError(errors.value, 'token');
        return;
      }

      errors.value = {};
      error.value = payload.message || 'Unable to reset password.';
      return;
    }

    window.location.assign(payload.redirect || '/admin/login');
  } catch (e) {
    errors.value = {};
    error.value = 'Unable to reset password. Please try again.';
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
  submitResetPassword();
}
</script>

<template>
  <VfAuthLayout
    class="admin-auth-layout"
    title="Choose a new password"
    description="Create a new password for your admin account"
  >
    <VfThemeSwitch class="auth-panel__theme" variant="switch" size="sm" />

    <form class="auth-form" method="post" action="/admin/reset-password" novalidate @submit.prevent="submitResetPassword" @keydown.enter="submitOnEnter">
      <CmAlert v-if="error" tone="danger" title="Reset password failed">
        {{ error }}
      </CmAlert>

      <div class="field">
        <label for="new-password">New password</label>
        <CmInput
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
        <CmInput
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
        <CmLink href="/admin/login" underline="none">
          sign in
        </CmLink>
      </div>

      <CmButton type="submit" :loading="loading" block>
        {{ loading ? 'Updating...' : 'Update password' }}
      </CmButton>
    </form>

    <template #footer>
      <AuthFooter />
    </template>
  </VfAuthLayout>
</template>
