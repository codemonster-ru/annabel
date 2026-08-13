<script setup>
import { onMounted, ref, watch } from 'vue';
import { CmAlert } from '@codemonster-ru/ui-vue';
import { CmCard } from '@codemonster-ru/ui-vue';
import AppDataTable from '../../../../../resources/js/components/AppDataTable.vue';
import CmTabs from '../../../../../resources/js/components/AppTabs.vue';
import { formatDateTime } from '../../../../Admin/resources/js/support/dateTime';

defineProps({ user: { type: Object, default: null } });

const tabs = [
  { value: 'updates', label: 'Updates' },
  { value: 'components', label: 'Components' },
];
const channelLabels = {
  stable: 'Stable',
  beta: 'Beta',
  nightly: 'Nightly',
};

const columns = [
  { key: 'name', header: 'Component', verticalAlign: 'middle' },
  { key: 'installed_version', header: 'Installed version', verticalAlign: 'middle' },
  { key: 'available_version', header: 'Available version', verticalAlign: 'middle' },
];

const cmsVersion = ref('');
const latestVersion = ref('');
const channel = ref('');
const lastCheckedAt = ref('');
const lastSuccessfulUpdateAt = ref('');
const components = ref([]);
const activeTab = ref('updates');
const page = ref(1);
const pageSize = ref(10);
const totalComponents = ref(0);
const loading = ref(true);
const error = ref('');

function channelLabel(value) {
  return channelLabels[value] || value || 'Loading…';
}

function historyDate(value) {
  return value ? formatDateTime(value) : 'Never';
}

async function loadSystem() {
  loading.value = true;
  error.value = '';
  const query = new URLSearchParams({
    page: String(page.value),
    per_page: String(pageSize.value),
  });

  try {
    const response = await fetch(`/admin/settings/system/updates/data?${query}`, {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
    });
    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
      throw new Error(payload.message || 'Unable to load system update information.');
    }

    cmsVersion.value = payload.cms_version || 'unknown';
    latestVersion.value = payload.latest_version || '';
    channel.value = payload.channel || 'stable';
    lastCheckedAt.value = payload.last_checked_at || '';
    lastSuccessfulUpdateAt.value = payload.last_successful_update_at || '';
    components.value = Array.isArray(payload.components)
      ? payload.components
      : [];
    totalComponents.value = Number.isFinite(payload.total) ? payload.total : 0;
  } catch (exception) {
    error.value = exception instanceof Error
      ? exception.message
      : 'Unable to load system update information.';
  } finally {
    loading.value = false;
  }
}

onMounted(loadSystem);
watch([page, pageSize], loadSystem);
</script>

<template>
  <div class="system-update-screen">
    <CmAlert v-if="error" tone="danger" title="System updates">{{ error }}</CmAlert>
    <CmTabs id="system-update-tabs" v-model="activeTab" :items="tabs">
      <template #panel="{ activeValue }">
        <div class="system-update-screen__panel">
          <CmCard v-if="activeValue === 'updates'">
            <table class="system-update-screen__details">
              <tbody>
                <tr>
                  <th scope="row">Installed version</th>
                  <td><strong>{{ cmsVersion || 'Loading…' }}</strong></td>
                </tr>
                <tr>
                  <th scope="row">Latest version</th>
                  <td><strong>{{ latestVersion || 'Not checked' }}</strong></td>
                </tr>
                <tr>
                  <th scope="row">Update channel</th>
                  <td><strong>{{ channelLabel(channel) }}</strong></td>
                </tr>
                <tr>
                  <th scope="row">Last checked</th>
                  <td><strong>{{ historyDate(lastCheckedAt) }}</strong></td>
                </tr>
                <tr>
                  <th scope="row">Last successful update</th>
                  <td><strong>{{ historyDate(lastSuccessfulUpdateAt) }}</strong></td>
                </tr>
                <tr>
                  <th scope="row">Status</th>
                  <td><strong>Update service not configured</strong></td>
                </tr>
              </tbody>
            </table>
          </CmCard>

          <AppDataTable
            v-else-if="activeValue === 'components'"
            :columns="columns"
            :rows="components"
            row-key="name"
            striped
            column-dividers
            :loading="loading"
            pagination
            :page="page"
            :page-size="pageSize"
            :total-rows="totalComponents"
            empty-text="No system components found"
            @update:page="page = $event"
            @update:page-size="pageSize = $event"
          >
            <template #cell-name="{ value }"><strong>{{ value }}</strong></template>
            <template #cell-installed_version="{ value }">{{ value || '—' }}</template>
            <template #cell-available_version="{ value }">{{ value || '—' }}</template>
          </AppDataTable>
        </div>
      </template>
    </CmTabs>
  </div>
</template>

<style scoped>
.system-update-screen {
  display: grid;
  gap: var(--cm-space-6);
}

.system-update-screen__panel {
  padding-block-start: var(--cm-space-6);
}

.system-update-screen__details {
  width: 100%;
  border-collapse: collapse;
}

.system-update-screen__details th,
.system-update-screen__details td {
  padding: var(--cm-space-2) 0;
  text-align: start;
}

.system-update-screen__details th {
  width: 14rem;
  padding-inline-end: var(--cm-space-6);
  color: var(--cm-color-text-muted);
  font-weight: inherit;
}

</style>
