<script setup>
import { ref } from 'vue';

const props = defineProps({
  boot: {
    type: Object,
    default: () => ({}),
  },
});

const authenticated = ref(Boolean(props.boot.authenticated));
const csrfToken = ref(props.boot.csrfToken || '');
const user = ref(props.boot.user || null);
const modules = ref(props.boot.modules || {});
const email = ref('');
const password = ref('');
const error = ref('');
const loading = ref(false);

async function login() {
  error.value = '';
  loading.value = true;

  const body = new FormData();
  body.append('_token', csrfToken.value);
  body.append('email', email.value);
  body.append('password', password.value);

  try {
    const response = await fetch('/admin/login', {
      method: 'POST',
      headers: { Accept: 'application/json' },
      body,
      credentials: 'same-origin',
    });
    const payload = await response.json();

    if (!response.ok) {
      error.value = payload.message || 'Sign in failed';
      return;
    }

    authenticated.value = Boolean(payload.authenticated);
    csrfToken.value = payload.csrfToken || csrfToken.value;
    user.value = payload.user || null;
    modules.value = payload.modules || {};
    password.value = '';
  } catch (e) {
    error.value = 'Unable to sign in. Please try again.';
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
      error.value = payload.message || 'Unable to sign out.';
      return;
    }

    authenticated.value = false;
    csrfToken.value = payload.csrfToken || csrfToken.value;
    user.value = null;
    modules.value = {};
  } catch (e) {
    error.value = 'Unable to sign out. Please try again.';
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <section v-if="!authenticated" class="login-screen">
    <form class="login" method="post" action="/admin/login" @submit.prevent="login">
      <h1>Annabel CMS Admin</h1>
      <p class="hint">Sign in to continue</p>

      <p v-if="error" class="error">{{ error }}</p>

      <label for="email">Email</label>
      <input id="email" v-model="email" type="email" autocomplete="email" required>

      <label for="password">Password</label>
      <input id="password" v-model="password" type="password" autocomplete="current-password" required>

      <button type="submit" :disabled="loading">
        {{ loading ? 'Signing in...' : 'Sign in' }}
      </button>
    </form>
  </section>

  <section v-else class="admin-shell">
    <header class="topbar">
      <div>
        <h1>Annabel CMS Admin - Dashboard</h1>
        <p v-if="user && user.email" class="signed-in">Signed in as {{ user.email }}</p>
      </div>
      <button type="button" :disabled="loading" @click="logout">Logout</button>
    </header>

    <main class="panel">
      <h2>Loaded Modules</h2>
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Class</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(moduleClass, name) in modules" :key="name">
            <td>{{ name }}</td>
            <td><code>{{ moduleClass }}</code></td>
          </tr>
        </tbody>
      </table>
    </main>
  </section>
</template>
