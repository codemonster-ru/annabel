<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { VfAlert } from '@codemonster-ru/vueforge-core/alert';
import { VfDataTable } from '@codemonster-ru/vueforge-core/data-table';
import { VfDropdown } from '@codemonster-ru/vueforge-core/dropdown';
import { VfIconButton } from '@codemonster-ru/vueforge-core/icon-button';
import { VfMenu, VfMenuItem } from '@codemonster-ru/vueforge-core/menu';
import { icons } from '@codemonster-ru/vueforge-icons';

const props = defineProps({ user: { type: Object, default: null } });
const canManage = computed(() => props.user?.roles?.includes('admin') || props.user?.permissions?.includes('modules.manage'));

const dataColumns = [
  { key: 'name', header: 'Name', verticalAlign: 'middle' },
  { key: 'author', header: 'Developer', verticalAlign: 'middle' },
  { key: 'version', header: 'Version', verticalAlign: 'middle' },
  { key: 'is_installed', header: 'Status', verticalAlign: 'middle' },
  { key: 'is_enabled', header: 'Active', verticalAlign: 'middle' },
  { key: 'dependencies', header: 'Dependencies', verticalAlign: 'middle' },
];

const rows = ref([]);
const columns = computed(() => (
  canManage.value && rows.value.some((row) => row.can_enable || row.can_disable || row.can_install || row.can_uninstall || row.can_update)
    ? [
        { key: 'actions', header: '', width: '1%', align: 'center', verticalAlign: 'middle' },
        ...dataColumns,
      ]
    : dataColumns
));
const page = ref(1);
const pageSize = ref(10);
const totalRows = ref(0);
const loading = ref(true);
const changingModule = ref('');
const csrfToken = ref('');
const error = ref('');
const success = ref('');

async function loadModules() {
  loading.value = true;
  error.value = '';
  const query = new URLSearchParams({
    page: String(page.value),
    per_page: String(pageSize.value),
  });

  try {
    const response = await fetch(`/admin/settings/modules/data?${query}`, {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
    });
    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
      throw new Error(payload.message || 'Unable to load modules.');
    }

    rows.value = Array.isArray(payload.data) ? payload.data : [];
    totalRows.value = Number.isFinite(payload.total) ? payload.total : 0;
    csrfToken.value = payload.csrf_token || '';
  } catch (exception) {
    error.value = exception instanceof Error ? exception.message : 'Unable to load modules.';
  } finally {
    loading.value = false;
  }
}

async function runAction(module, action) {
  const allowed = {
    enable: module?.can_enable,
    disable: module?.can_disable,
    install: module?.can_install,
    uninstall: module?.can_uninstall,
    update: module?.can_update,
  };

  if (!canManage.value || !allowed[action] || changingModule.value) return;
  if (action === 'uninstall' && !window.confirm(`Uninstall module ${module.name}? Its data will be preserved.`)) return;

  changingModule.value = module.name;
  error.value = '';
  success.value = '';
  const body = new FormData();
  body.append('_token', csrfToken.value);

  try {
    const response = await fetch(`/admin/settings/modules/data/${encodeURIComponent(module.name)}/${action}`, {
      method: 'POST',
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
      body,
    });
    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
      throw new Error(payload.message || 'Unable to change module state.');
    }

    success.value = payload.message || 'Module state updated successfully.';
    await loadModules();
  } catch (exception) {
    error.value = exception instanceof Error ? exception.message : 'Unable to change module state.';
  } finally {
    changingModule.value = '';
  }
}

onMounted(loadModules);
watch([page, pageSize], loadModules);
</script>

<template>
  <div class="modules-screen">
    <VfAlert v-if="error" tone="danger" title="Modules">{{ error }}</VfAlert>
    <VfAlert v-if="success" tone="success" title="Modules">{{ success }}</VfAlert>
    <VfDataTable
      :columns="columns"
      :rows="rows"
      row-key="name"
      striped
      column-dividers
      :loading="loading"
      pagination
      pagination-mode="manual"
      :page="page"
      :page-size="pageSize"
      :total-rows="totalRows"
      empty-text="No modules found"
      @update:page="page = $event"
      @update:page-size="pageSize = $event"
    >
      <template #cell-actions="{ row }">
        <VfDropdown v-if="canManage && (row.can_enable || row.can_disable || row.can_install || row.can_uninstall || row.can_update)" placement="bottom-start">
          <template #trigger>
            <VfIconButton
              :icon="icons.bars"
              variant="ghost"
              size="sm"
              :aria-label="`Actions for ${row.name}`"
              :title="`Actions for ${row.name}`"
              :disabled="Boolean(changingModule)"
            />
          </template>
          <VfMenu>
            <VfMenuItem
              v-if="row.can_disable"
              label="Deactivate"
              :icon="icons.ban"
              @select="runAction(row, 'disable')"
            />
            <VfMenuItem
              v-if="row.can_enable"
              label="Activate"
              :icon="icons.checkCircle"
              @select="runAction(row, 'enable')"
            />
            <VfMenuItem
              v-if="row.can_install"
              label="Install"
              :icon="icons.download"
              @select="runAction(row, 'install')"
            />
            <VfMenuItem
              v-if="row.can_update"
              label="Update"
              :icon="icons.refresh"
              @select="runAction(row, 'update')"
            />
            <VfMenuItem
              v-if="row.can_uninstall"
              label="Uninstall"
              :icon="icons.trash"
              tone="danger"
              @select="runAction(row, 'uninstall')"
            />
          </VfMenu>
        </VfDropdown>
      </template>
      <template #cell-name="{ value }"><strong>{{ value }}</strong></template>
      <template #cell-version="{ value, row }">
        <span>{{ value }}</span>
        <small v-if="row.is_installed && row.installed_version !== value" class="modules-screen__version">
          Installed: {{ row.installed_version || 'unknown' }}
        </small>
      </template>
      <template #cell-author="{ value }">
        <a v-if="value?.url" :href="value.url" target="_blank" rel="noopener noreferrer">
          {{ value.name }}
        </a>
        <span v-else :class="{ 'modules-screen__empty': !value }">
          {{ value?.name || 'Unknown' }}
        </span>
      </template>
      <template #cell-is_installed="{ value }">
        <span :class="['modules-screen__status', { 'modules-screen__status--installed': value }]">
          {{ value ? 'Installed' : 'Not installed' }}
        </span>
      </template>
      <template #cell-is_enabled="{ value, row }">
        <span v-if="row.is_installed" :class="['modules-screen__status', { 'modules-screen__status--installed': value }]">
          {{ value ? 'Enabled' : 'Disabled' }}
        </span>
        <span v-else class="modules-screen__empty">—</span>
      </template>
      <template #cell-dependencies="{ value }">
        <span :class="{ 'modules-screen__empty': value.length === 0 }">
          {{ value.length > 0 ? value.join(', ') : 'None' }}
        </span>
      </template>
    </VfDataTable>
  </div>
</template>

<style scoped>
.modules-screen {
  display: grid;
  gap: var(--vf-section-gap);
}

.modules-screen__empty {
  color: var(--vf-color-muted);
}

.modules-screen__status {
  color: var(--vf-color-muted);
}

.modules-screen__status--installed {
  color: var(--vf-color-success);
}

.modules-screen__version {
  display: block;
  color: var(--vf-color-muted);
}
</style>
