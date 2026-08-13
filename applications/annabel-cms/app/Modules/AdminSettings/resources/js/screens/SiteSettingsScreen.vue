<script setup>
import { onMounted, ref } from 'vue';
import { CmAlert } from '@codemonster-ru/ui-vue';
import { CmButton } from '@codemonster-ru/ui-vue';
import { CmCard } from '@codemonster-ru/ui-vue';
import CmField from '../../../../../resources/js/components/AppField.vue';
import AppFormLayout from '../../../../Admin/resources/js/components/AppFormLayout.vue';
import { CmInput } from '@codemonster-ru/ui-vue';

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
    <CmButton
      type="submit"
      form="site-settings-form"
      :loading="saving"
      :disabled="loading"
    >
      {{ saving ? 'Saving...' : 'Save settings' }}
    </CmButton>
  </Teleport>

  <form id="site-settings-form" class="site-settings-form" novalidate @submit.prevent="saveSettings">
    <CmCard>
      <AppFormLayout mode="responsive" label-width="minmax(14rem, 25%)">
        <CmAlert v-if="error" tone="danger" title="Site settings">
          {{ error }}
        </CmAlert>
        <CmAlert v-if="success" tone="success" title="Site settings">
          {{ success }}
        </CmAlert>

        <CmField label="Site name" :error="firstError('site_name')" required>
          <template #default="{ controlId, describedBy, invalid }">
            <CmInput
              :id="controlId"
              v-model="settings.site_name"
              :aria-describedby="describedBy"
              :invalid="invalid"
              :disabled="loading || !canUpdate()"
              required
            />
          </template>
        </CmField>

        <CmField label="Locale" description="A language tag such as en or en-US." :error="firstError('locale')" required>
          <template #default="{ controlId, describedBy, invalid }">
            <CmInput
              :id="controlId"
              v-model="settings.locale"
              :aria-describedby="describedBy"
              :invalid="invalid"
              :disabled="loading || !canUpdate()"
              required
            />
          </template>
        </CmField>
      </AppFormLayout>
    </CmCard>
  </form>
</template>
