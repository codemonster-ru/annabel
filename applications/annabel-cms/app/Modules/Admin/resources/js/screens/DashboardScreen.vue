<script setup>
import { ref } from 'vue';
import { VfButton } from '@codemonster-ru/vueforge-core/button';
import { VfPanel } from '@codemonster-ru/vueforge-core/panel';
import { VfTable } from '@codemonster-ru/vueforge-core/table';
import { VfThemeSwitch } from '@codemonster-ru/vueforge-core/theme-switch';

const props = defineProps({
  csrfToken: {
    type: String,
    required: true,
  },
  user: {
    type: Object,
    default: null,
  },
  modules: {
    type: Object,
    default: () => ({}),
  },
});

const error = ref('');
const loading = ref(false);

async function logout() {
  error.value = '';
  loading.value = true;

  const body = new FormData();
  body.append('_token', props.csrfToken);

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

    window.location.assign('/admin/login');
  } catch (e) {
    error.value = 'Unable to sign out. Please try again.';
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <section class="admin-shell">
    <header class="topbar">
      <div>
        <h1>Annabel CMS</h1>
        <p v-if="user && user.email" class="signed-in">Signed in as {{ user.email }}</p>
        <p v-if="error" class="field__error">{{ error }}</p>
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
</template>
