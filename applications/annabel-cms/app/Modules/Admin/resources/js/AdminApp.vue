<script setup>
import { ref } from 'vue';
import { VfThemeProvider } from '@codemonster-ru/vueforge-core';
import { VfAlert } from '@codemonster-ru/vueforge-core/alert';
import { VfButton } from '@codemonster-ru/vueforge-core/button';
import { VfCheckbox } from '@codemonster-ru/vueforge-core/checkbox';
import { VfInput } from '@codemonster-ru/vueforge-core/input';
import { VfLink } from '@codemonster-ru/vueforge-core/link';
import { VfPanel } from '@codemonster-ru/vueforge-core/panel';
import { VfTable } from '@codemonster-ru/vueforge-core/table';
import { VfThemeSwitch } from '@codemonster-ru/vueforge-core/theme-switch';
import { VfAuthLayout } from '@codemonster-ru/vueforge-layouts/auth-layout';

const props = defineProps({
  boot: {
    type: Object,
    default: () => ({}),
  },
});

const authenticated = ref(Boolean(props.boot.authenticated));
const screen = ref(props.boot.screen || 'login');
const csrfToken = ref(props.boot.csrfToken || '');
const user = ref(props.boot.user || null);
const modules = ref(props.boot.modules || {});
const loginValue = ref('');
const password = ref('');
const remember = ref(false);
const resetEmail = ref('');
const resetToken = ref(props.boot.resetToken || '');
const newPassword = ref('');
const newPasswordConfirmation = ref('');
const resetNotice = ref('');
const loginError = ref('');
const resetError = ref('');
const resetPasswordError = ref('');
const loginErrors = ref({});
const resetErrors = ref({});
const resetPasswordErrors = ref({});
const loading = ref(false);
const currentYear = new Date().getFullYear();
const copyrightYears = currentYear > 2026 ? `2026-${currentYear}` : '2026';

function firstError(errors, field) {
  const source = errors && typeof errors === 'object' && 'value' in errors
    ? errors.value
    : errors || {};
  const messages = source[field];

  return Array.isArray(messages) && messages.length > 0
    ? messages[0]
    : '';
}

async function login() {
  loginError.value = '';
  loginErrors.value = {};
  loading.value = true;

  const body = new FormData();
  body.append('_token', csrfToken.value);
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
        loginErrors.value = payload.errors || {};
        return;
      }

      loginError.value = payload.message || 'Sign in failed';
      return;
    }

    window.location.assign('/admin');
  } catch (e) {
    loginError.value = 'Unable to sign in. Please try again.';
  } finally {
    loading.value = false;
  }
}

async function requestPasswordReset() {
  resetNotice.value = '';
  resetError.value = '';
  resetErrors.value = {};
  loading.value = true;

  const body = new FormData();
  body.append('_token', csrfToken.value);
  body.append('email', resetEmail.value);

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
        resetErrors.value = payload.errors || {};
        return;
      }

      resetError.value = payload.message || 'Unable to send reset link.';
      return;
    }

    resetNotice.value = payload.message || 'If an admin account with that email exists, we have sent a password reset link.';
  } catch (e) {
    resetError.value = 'Unable to send reset link. Please try again.';
  } finally {
    loading.value = false;
  }
}

async function submitResetPassword() {
  resetPasswordError.value = '';
  resetPasswordErrors.value = {};
  loading.value = true;

  const body = new FormData();
  body.append('_token', csrfToken.value);
  body.append('token', resetToken.value);
  body.append('password', newPassword.value);
  body.append('password_confirmation', newPasswordConfirmation.value);

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
        resetPasswordErrors.value = payload.errors || {};
        resetPasswordError.value = firstError(resetPasswordErrors, 'token');
        return;
      }

      resetPasswordError.value = payload.message || 'Unable to reset password.';
      return;
    }

    window.location.assign(payload.redirect || '/admin/login');
  } catch (e) {
    resetPasswordError.value = 'Unable to reset password. Please try again.';
  } finally {
    loading.value = false;
  }
}

async function logout() {
  loading.value = true;

  const body = new FormData();
  body.append('_token', csrfToken.value);

  try {
    const response = await fetch('/admin/logout', {
      method: 'POST',
      headers: { Accept: 'application/json' },
      body,
      credentials: 'same-origin',
    });
    const payload = await response.json();

    if (!response.ok) {
      loginError.value = payload.message || 'Unable to sign out.';
      return;
    }

    window.location.assign('/admin/login');
  } catch (e) {
    loginError.value = 'Unable to sign out. Please try again.';
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <VfThemeProvider>
    <VfAuthLayout
      v-if="!authenticated && screen === 'login'"
      class="admin-auth-layout"
      title="Annabel CMS"
      description="Sign in to your admin panel"
    >
      <VfThemeSwitch
        class="auth-panel__theme"
        variant="switch"
        size="sm"
      />

      <form class="auth-form" method="post" action="/admin/login" novalidate @submit.prevent="login">
        <VfAlert v-if="loginError" tone="danger" title="Sign in failed">
          {{ loginError }}
        </VfAlert>

        <div class="field">
          <label for="login">Username or email</label>
          <VfInput
            id="login"
            v-model="loginValue"
            type="text"
            autocomplete="username"
            :invalid="Boolean(firstError(loginErrors, 'login'))"
          />
          <p v-if="firstError(loginErrors, 'login')" class="field__error">
            {{ firstError(loginErrors, 'login') }}
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
            :invalid="Boolean(firstError(loginErrors, 'password'))"
          />
          <p v-if="firstError(loginErrors, 'password')" class="field__error">
            {{ firstError(loginErrors, 'password') }}
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
        <div class="auth-footer">
          <span>
            &copy; {{ copyrightYears }}
            <VfLink href="https://codemonster.net" target="_blank" rel="noopener noreferrer" underline="none">
              Codemonster
            </VfLink>.
            All rights reserved.
          </span>
        </div>
      </template>
    </VfAuthLayout>

    <VfAuthLayout
      v-else-if="!authenticated && screen === 'forgot-password'"
      class="admin-auth-layout"
      title="Reset your password"
      description="Enter your admin account email"
    >
      <VfThemeSwitch
        class="auth-panel__theme"
        variant="switch"
        size="sm"
      />

      <form class="auth-form" method="post" action="/admin/forgot-password" novalidate @submit.prevent="requestPasswordReset">
        <VfAlert v-if="resetError" tone="danger" title="Password recovery">
          {{ resetError }}
        </VfAlert>

        <VfAlert v-if="resetNotice" tone="info" title="Password recovery">
          {{ resetNotice }}
        </VfAlert>

        <div class="field">
          <label for="reset-email">Email</label>
          <VfInput
            id="reset-email"
            v-model="resetEmail"
            type="email"
            autocomplete="email"
            :invalid="Boolean(firstError(resetErrors, 'email'))"
          />
          <p v-if="firstError(resetErrors, 'email')" class="field__error">
            {{ firstError(resetErrors, 'email') }}
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
        <div class="auth-footer">
          <span>
            &copy; {{ copyrightYears }}
            <VfLink href="https://codemonster.net" target="_blank" rel="noopener noreferrer" underline="none">
              Codemonster
            </VfLink>.
            All rights reserved.
          </span>
        </div>
      </template>
    </VfAuthLayout>

    <VfAuthLayout
      v-else-if="!authenticated && screen === 'reset-password'"
      class="admin-auth-layout"
      title="Choose a new password"
      description="Create a new password for your admin account"
    >
      <VfThemeSwitch
        class="auth-panel__theme"
        variant="switch"
        size="sm"
      />

      <form class="auth-form" method="post" action="/admin/reset-password" novalidate @submit.prevent="submitResetPassword">
        <VfAlert v-if="resetPasswordError" tone="danger" title="Reset password failed">
          {{ resetPasswordError }}
        </VfAlert>

        <div class="field">
          <label for="new-password">New password</label>
          <VfInput
            id="new-password"
            v-model="newPassword"
            type="password"
            autocomplete="new-password"
            password-reveal
            :invalid="Boolean(firstError(resetPasswordErrors, 'password'))"
          />
          <p v-if="firstError(resetPasswordErrors, 'password')" class="field__error">
            {{ firstError(resetPasswordErrors, 'password') }}
          </p>
        </div>

        <div class="field">
          <label for="new-password-confirmation">Confirm new password</label>
          <VfInput
            id="new-password-confirmation"
            v-model="newPasswordConfirmation"
            type="password"
            autocomplete="new-password"
            password-reveal
            :invalid="Boolean(firstError(resetPasswordErrors, 'password_confirmation'))"
          />
          <p v-if="firstError(resetPasswordErrors, 'password_confirmation')" class="field__error">
            {{ firstError(resetPasswordErrors, 'password_confirmation') }}
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
        <div class="auth-footer">
          <span>
            &copy; {{ copyrightYears }}
            <VfLink href="https://codemonster.net" target="_blank" rel="noopener noreferrer" underline="none">
              Codemonster
            </VfLink>.
            All rights reserved.
          </span>
        </div>
      </template>
    </VfAuthLayout>

    <section v-else class="admin-shell">
      <header class="topbar">
        <div>
          <h1>Annabel CMS</h1>
          <p v-if="user && user.email" class="signed-in">Signed in as {{ user.email }}</p>
        </div>
        <div class="topbar__actions">
          <VfThemeSwitch variant="button" size="sm" />
          <VfButton type="button" variant="secondary" :disabled="loading" @click="logout">
            Logout
          </VfButton>
        </div>
      </header>

      <main>
        <VfPanel title="Loaded Modules">
          <VfTable caption="Loaded Admin modules" compact striped>
            <template #header>
              <tr>
                <th>Name</th>
                <th>Class</th>
              </tr>
            </template>
            <tr v-for="(moduleClass, name) in modules" :key="name">
              <td>{{ name }}</td>
              <td><code>{{ moduleClass }}</code></td>
            </tr>
          </VfTable>
        </VfPanel>
      </main>
    </section>
  </VfThemeProvider>
</template>

<style scoped>
.auth-grid {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.auth-section {
  display: grid;
  gap: 1rem;
}

.auth-section__title {
  margin: 0;
  color: var(--vf-color-text-muted);
  font-size: 0.95rem;
  font-weight: 600;
}

.auth-hint {
  margin: 0;
  color: var(--vf-color-text-muted);
  font-size: 0.875rem;
}

.auth-hint code {
  word-break: break-all;
}

@media (max-width: 640px) {
  .auth-grid {
    grid-template-columns: 1fr;
  }
}
</style>
