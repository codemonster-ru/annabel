<script setup>
import { ref, watch } from 'vue';
import { VfAlert } from '@codemonster-ru/vueforge-core/alert';
import { VfDataTable } from '@codemonster-ru/vueforge-core/data-table';

const columns = [
  { key: 'id', header: 'ID' },
  { key: 'username', header: 'Username' },
  { key: 'email', header: 'Email' },
  { key: 'is_active', header: 'Status' },
  { key: 'updated_at', header: 'Updated' },
];
const dateFormatter = new Intl.DateTimeFormat(undefined, {
  dateStyle: 'medium',
  timeStyle: 'short',
});
const rows = ref([]);
const page = ref(1);
const pageSize = ref(10);
const totalRows = ref(0);
const loading = ref(false);
const error = ref('');
let requestId = 0;

function formatDate(value) {
  const date = new Date(value);

  return Number.isNaN(date.getTime()) ? '—' : dateFormatter.format(date);
}

async function loadUsers() {
  const currentRequestId = ++requestId;
  const query = new URLSearchParams({
    page: String(page.value),
    per_page: String(pageSize.value),
  });

  loading.value = true;
  error.value = '';

  try {
    const response = await fetch(`/admin/settings/users/data?${query}`, {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
    });
    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
      throw new Error(payload.message || 'Unable to load users.');
    }

    if (currentRequestId !== requestId) {
      return;
    }

    rows.value = Array.isArray(payload.data) ? payload.data : [];
    totalRows.value = Number.isFinite(payload.total) ? payload.total : 0;
  } catch (exception) {
    if (currentRequestId !== requestId) {
      return;
    }

    rows.value = [];
    totalRows.value = 0;
    error.value = exception instanceof Error ? exception.message : 'Unable to load users.';
  } finally {
    if (currentRequestId === requestId) {
      loading.value = false;
    }
  }
}

watch([page, pageSize], loadUsers, { immediate: true });
</script>

<template>
  <VfAlert v-if="error" tone="danger" title="Users could not be loaded">
    {{ error }}
  </VfAlert>
  <VfDataTable
    :columns="columns"
    :rows="rows"
    row-key="id"
    striped
    column-dividers
    pagination
    pagination-mode="manual"
    :page="page"
    :page-size="pageSize"
    :total-rows="totalRows"
    :loading="loading"
    empty-text="No users found"
    @update:page="page = $event"
    @update:page-size="pageSize = $event"
  >
    <template #cell-is_active="{ row }">
      {{ row.is_active ? 'Active' : 'Inactive' }}
    </template>
    <template #cell-updated_at="{ value }">
      {{ formatDate(value) }}
    </template>
  </VfDataTable>
</template>
