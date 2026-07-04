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
});

const email = ref('');
const notice = ref('');
const error = ref('');
const errors = ref({});
const loading = ref(false);

async function requestPasswordReset() {
  notice.value = '';
  error.value = '';
  errors.value = {};
  loading.value = true;

  const body = new FormData();
  body.append('_token', props.csrfToken);
  body.append('email', email.value);

  try {
    const response = await fetch('/admin/forgot-password', {
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

      error.value = payload.message || 'Unable to send reset link.';
      return;
    }

    notice.value = payload.message || 'If an admin account with that email exists, we have sent a password reset link.';
  } catch (e) {
    error.value = 'Unable to send reset link. Please try again.';
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <VfAuthLayout
    class="admin-auth-layout"
    title="Reset your password"
    description="Enter your admin account email"
  >
    <VfThemeSwitch class="auth-panel__theme" variant="switch" size="sm" />

    <form class="auth-form" method="post" action="/admin/forgot-password" novalidate @submit.prevent="requestPasswordReset">
      <VfAlert v-if="error" tone="danger" title="Password recovery">
        {{ error }}
      </VfAlert>

      <VfAlert v-if="notice" tone="info" title="Password recovery">
        {{ notice }}
      </VfAlert>

      <div class="field">
        <label for="reset-email">Email</label>
        <VfInput
          id="reset-email"
          v-model="email"
          type="email"
          autocomplete="email"
          :invalid="Boolean(firstError(errors, 'email'))"
        />
        <p v-if="firstError(errors, 'email')" class="field__error">
          {{ firstError(errors, 'email') }}
        </p>
      </div>

      <div class="auth-form__back">
        <span class="auth-form__back-label">Back to</span>
        <VfLink href="/admin/login" underline="none">
          sign in
        </VfLink>
      </div>

      <VfButton type="submit" :disabled="loading" block>
        {{ loading ? 'Sending...' : 'Send reset link' }}
      </VfButton>
    </form>

    <template #footer>
      <AuthFooter />
    </template>
  </VfAuthLayout>
</template>
