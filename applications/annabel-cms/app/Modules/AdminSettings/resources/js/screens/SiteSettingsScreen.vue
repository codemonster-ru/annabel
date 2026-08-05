<script setup>
import { onMounted, ref } from 'vue';
import { VfAlert } from '@codemonster-ru/vueforge-core/alert';
import { VfButton } from '@codemonster-ru/vueforge-core/button';
import { VfCard } from '@codemonster-ru/vueforge-core/card';
import { VfField } from '@codemonster-ru/vueforge-core/field';
import { VfInput } from '@codemonster-ru/vueforge-core/input';

const props = defineProps({ user: { type: Object, default: null } });
const canUpdate = () => props.user?.roles?.includes('admin') || props.user?.permissions?.includes('settings.update');

const settings = ref({
  site_name: '',
  locale: '',
});
const csrfToken = ref('');
const loading = ref(true);
const saving = ref(false);
const error = ref('');
const success = ref('');
const errors = ref({});

function firstError(field) {
  const messages = errors.value[field];

  return Array.isArray(messages) && messages.length > 0 ? messages[0] : '';
}

async function loadSettings() {
  loading.value = true;
  error.value = '';

  try {
    const response = await fetch('/admin/settings/general/data', {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
    });
    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
      throw new Error(payload.message || 'Unable to load site settings.');
    }

    settings.value = { ...settings.value, ...payload.settings };
    csrfToken.value = payload.csrf_token || '';
  } catch (exception) {
    error.value = exception instanceof Error ? exception.message : 'Unable to load site settings.';
  } finally {
    loading.value = false;
  }
}

async function saveSettings() {
  if (saving.value) {
    return;
  }

  saving.value = true;
  error.value = '';
  success.value = '';
  errors.value = {};

  const body = new FormData();
  body.append('_token', csrfToken.value);

  for (const [field, value] of Object.entries(settings.value)) {
    body.append(field, value);
  }

  try {
    const response = await fetch('/admin/settings/general/data', {
      method: 'POST',
      headers: { Accept: 'application/json' },
      body,
      credentials: 'same-origin',
    });
    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
      if (response.status === 422) {
        errors.value = payload.errors || {};
      }

      throw new Error(payload.message || 'Unable to save site settings.');
    }

    settings.value = { ...settings.value, ...payload.settings };
    success.value = payload.message || 'Site settings updated successfully.';
  } catch (exception) {
    error.value = exception instanceof Error ? exception.message : 'Unable to save site settings.';
  } finally {
    saving.value = false;
  }
}

onMounted(loadSettings);
</script>

<template>
  <Teleport v-if="canUpdate()" to="#admin-page-actions">
    <VfButton
      type="submit"
      form="site-settings-form"
      :loading="saving"
      :disabled="loading"
    >
      {{ saving ? 'Saving...' : 'Save settings' }}
    </VfButton>
  </Teleport>

  <form id="site-settings-form" class="site-settings-form" novalidate @submit.prevent="saveSettings">
    <VfCard>
      <div class="site-settings-form__fields">
        <VfAlert v-if="error" tone="danger" title="Site settings">
          {{ error }}
        </VfAlert>
        <VfAlert v-if="success" tone="success" title="Site settings">
          {{ success }}
        </VfAlert>

        <VfField label="Site name" :error="firstError('site_name')" required>
          <template #default="{ controlId, describedBy, invalid }">
            <VfInput
              :id="controlId"
              v-model="settings.site_name"
              :aria-describedby="describedBy"
              :invalid="invalid"
              :disabled="loading || !canUpdate()"
              required
            />
          </template>
        </VfField>

        <VfField label="Locale" description="A language tag such as en or en-US." :error="firstError('locale')" required>
          <template #default="{ controlId, describedBy, invalid }">
            <VfInput
              :id="controlId"
              v-model="settings.locale"
              :aria-describedby="describedBy"
              :invalid="invalid"
              :disabled="loading || !canUpdate()"
              required
            />
          </template>
        </VfField>
      </div>
    </VfCard>
  </form>
</template>

<style scoped>
.site-settings-form__fields {
  display: grid;
  gap: var(--vf-section-gap);
}
</style>
