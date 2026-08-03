<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { VfAlert } from '@codemonster-ru/vueforge-core/alert';
import { VfButton } from '@codemonster-ru/vueforge-core/button';
import { VfCard } from '@codemonster-ru/vueforge-core/card';
import { VfCheckbox } from '@codemonster-ru/vueforge-core/checkbox';
import { VfDataTable } from '@codemonster-ru/vueforge-core/data-table';
import { VfDropdown } from '@codemonster-ru/vueforge-core/dropdown';
import { VfField } from '@codemonster-ru/vueforge-core/field';
import { VfIconButton } from '@codemonster-ru/vueforge-core/icon-button';
import { VfInput } from '@codemonster-ru/vueforge-core/input';
import { VfMenu, VfMenuItem } from '@codemonster-ru/vueforge-core/menu';
import { VfTabs } from '@codemonster-ru/vueforge-core/tabs';
import { icons } from '@codemonster-ru/vueforge-icons';

const columns = [
  { key: 'actions', header: '', width: '1%', align: 'center', verticalAlign: 'middle' },
  { key: 'id', header: 'ID', width: '1%', align: 'center', verticalAlign: 'middle' },
  { key: 'username', header: 'Username', verticalAlign: 'middle' },
  { key: 'email', header: 'Email', verticalAlign: 'middle' },
  { key: 'is_active', header: 'Active', verticalAlign: 'middle' },
  { key: 'created_at', header: 'Created', verticalAlign: 'middle' },
  { key: 'updated_at', header: 'Updated', verticalAlign: 'middle' },
];
const columnLabels = {
  actions: 'Actions',
  id: 'ID',
  username: 'Username',
  email: 'Email',
  is_active: 'Active',
  created_at: 'Created',
  updated_at: 'Updated',
};
const dateFormatter = new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' });
const userFormTabs = [{ value: 'general', label: 'General' }];
const emptyUser = () => ({ id: null, username: '', email: '', password: '', password_confirmation: '', is_active: true });

const currentPath = typeof window !== 'undefined' ? window.location.pathname : '/admin/users';
const formMode = computed(() => currentPath === '/admin/users/create' || /^\/admin\/users\/\d+\/edit$/.test(currentPath));
const editId = computed(() => currentPath.match(/^\/admin\/users\/(\d+)\/edit$/)?.[1] || null);
const editing = computed(() => editId.value !== null);
const rows = ref([]);
const user = ref(emptyUser());
const visibleColumns = ref(columns.map((column) => column.key));
const page = ref(1);
const pageSize = ref(10);
const totalRows = ref(0);
const csrfToken = ref('');
const loading = ref(true);
const saving = ref(false);
const deleting = ref(false);
const preferencesSaving = ref(false);
const error = ref('');
const success = ref('');
const errors = ref({});
const activeTab = ref('general');

function firstError(field) {
  const messages = errors.value[field];
  return Array.isArray(messages) && messages.length > 0 ? messages[0] : '';
}

function formatDate(value) {
  if (value == null || value === '') return '—';
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? '—' : dateFormatter.format(date);
}

async function loadUsers() {
  loading.value = true;
  error.value = '';
  const query = new URLSearchParams({ page: String(page.value), per_page: String(pageSize.value) });

  try {
    const response = await fetch(`/admin/users/data?${query}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(payload.message || 'Unable to load users.');
    rows.value = Array.isArray(payload.data) ? payload.data : [];
    totalRows.value = Number.isFinite(payload.total) ? payload.total : 0;
    csrfToken.value = payload.csrf_token || '';
    if (Array.isArray(payload.visible_columns) && payload.visible_columns.includes('actions')) visibleColumns.value = payload.visible_columns;
  } catch (exception) {
    error.value = exception instanceof Error ? exception.message : 'Unable to load users.';
  } finally {
    loading.value = false;
  }
}

async function loadUser() {
  loading.value = true;
  error.value = '';
  try {
    const response = await fetch(`/admin/users/data/${editId.value}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(payload.message || 'Unable to load user.');
    user.value = { ...emptyUser(), ...payload.user };
    csrfToken.value = payload.csrf_token || '';
  } catch (exception) {
    error.value = exception instanceof Error ? exception.message : 'Unable to load user.';
  } finally {
    loading.value = false;
  }
}

async function saveColumnPreferences(next) {
  const previous = visibleColumns.value;
  visibleColumns.value = columns.map((column) => column.key).filter((key) => next.includes(key));
  preferencesSaving.value = true;
  const body = new FormData();
  body.append('_token', csrfToken.value);
  body.append('visible_columns', JSON.stringify(visibleColumns.value));
  try {
    const response = await fetch('/admin/users/preferences', { method: 'POST', headers: { Accept: 'application/json' }, body, credentials: 'same-origin' });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(payload.message || 'Unable to save column preferences.');
  } catch (exception) {
    visibleColumns.value = previous;
    error.value = exception instanceof Error ? exception.message : 'Unable to save column preferences.';
  } finally {
    preferencesSaving.value = false;
  }
}

function toggleColumn(key, value) {
  if (key === 'actions') return;
  const next = value ? [...visibleColumns.value, key] : visibleColumns.value.filter((column) => column !== key);
  saveColumnPreferences(next);
}

function toggleAllColumns(value) {
  saveColumnPreferences(value ? columns.map((column) => column.key) : ['actions']);
}

function editUser(value) {
  window.location.assign(`/admin/users/${value.id}/edit`);
}

function newUser() {
  window.location.assign('/admin/users/create');
}

function backToUsers() {
  window.location.assign('/admin/users');
}

async function saveUser() {
  if (saving.value) return;
  saving.value = true;
  error.value = '';
  success.value = '';
  errors.value = {};
  const body = new FormData();
  body.append('_token', csrfToken.value);
  body.append('username', user.value.username);
  body.append('email', user.value.email);
  body.append('password', user.value.password);
  body.append('password_confirmation', user.value.password_confirmation);
  body.append('is_active', user.value.is_active ? '1' : '0');
  const endpoint = editing.value ? `/admin/users/data/${user.value.id}` : '/admin/users/data';

  try {
    const response = await fetch(endpoint, { method: 'POST', headers: { Accept: 'application/json' }, body, credentials: 'same-origin' });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
      if (response.status === 422) errors.value = payload.errors || {};
      throw new Error(payload.message || 'Unable to save user.');
    }
    user.value = { ...emptyUser(), ...payload.user };
    success.value = payload.message || 'User saved successfully.';
    if (!editing.value) window.location.assign('/admin/users');
  } catch (exception) {
    error.value = exception instanceof Error ? exception.message : 'Unable to save user.';
  } finally {
    saving.value = false;
  }
}

async function deleteUser(value) {
  if (!value?.id || deleting.value || !window.confirm('Delete this user?')) return;
  deleting.value = true;
  const body = new FormData();
  body.append('_token', csrfToken.value);
  try {
    const response = await fetch(`/admin/users/data/${value.id}/delete`, { method: 'POST', headers: { Accept: 'application/json' }, body, credentials: 'same-origin' });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(payload.message || 'Unable to delete user.');
    window.location.assign('/admin/users');
  } catch (exception) {
    error.value = exception instanceof Error ? exception.message : 'Unable to delete user.';
  } finally {
    deleting.value = false;
  }
}

watch([page, pageSize], loadUsers);
onMounted(() => (formMode.value ? (editId.value ? loadUser() : (loading.value = false)) : loadUsers()));
</script>

<template>
  <div class="users-screen">
    <Teleport v-if="formMode" to="#admin-page-actions">
      <VfButton type="submit" form="users-user-form" :loading="saving" :disabled="loading || deleting">{{ saving ? 'Saving...' : 'Save user' }}</VfButton>
      <VfButton variant="secondary" :disabled="saving || deleting" @click="backToUsers">Back</VfButton>
    </Teleport>
    <Teleport v-else to="#admin-page-actions">
      <VfButton variant="primary" :disabled="loading || saving" @click="newUser">New user</VfButton>
    </Teleport>

    <VfAlert v-if="error" tone="danger" title="Users">
      {{ error }}
    </VfAlert>
    <VfAlert v-if="success" tone="success" title="Users">
      {{ success }}
    </VfAlert>

    <div v-if="!formMode" class="users-screen__list">
      <VfDataTable
        :columns="columns"
        :visible-column-keys="visibleColumns"
        :rows="rows"
        row-key="id"
        striped
        column-dividers
        :loading="loading"
        pagination
        pagination-mode="manual"
        :page="page"
        :page-size="pageSize"
        :total-rows="totalRows"
        empty-text="No users found"
        @update:page="page = $event"
        @update:page-size="pageSize = $event"
      >
        <template #header-actions>
          <VfDropdown placement="bottom-start" :close-on-select="false">
            <template #trigger>
              <VfIconButton :icon="icons.gear" variant="ghost" size="sm" aria-label="Configure columns" title="Configure columns" :disabled="preferencesSaving" />
            </template>
            <div class="users-screen__column-select-all">
              <VfCheckbox label="All columns" :model-value="visibleColumns.length === columns.length" :disabled="preferencesSaving" @update:model-value="toggleAllColumns" />
            </div>
            <VfCheckbox v-for="column in columns" :key="column.key" :model-value="visibleColumns.includes(column.key)" :label="columnLabels[column.key]" :disabled="column.key === 'actions' || preferencesSaving" @update:model-value="toggleColumn(column.key, $event)" />
          </VfDropdown>
        </template>
        <template #cell-actions="{ row }">
          <VfDropdown placement="bottom-start">
            <template #trigger>
              <VfIconButton :icon="icons.bars" variant="ghost" size="sm" :aria-label="`Actions for ${row.username}`" :title="`Actions for ${row.username}`" />
            </template>
            <VfMenu>
              <VfMenuItem label="Edit" :icon="icons.pencil" @select="editUser(row)" />
              <VfMenuItem label="Delete" :icon="icons.trash" tone="danger" @select="deleteUser(row)" />
            </VfMenu>
          </VfDropdown>
        </template>
        <template #cell-username="{ row }">
          <a class="users-screen__user-link" :href="`/admin/users/${row.id}/edit`"><strong>{{ row.username }}</strong></a>
        </template>
        <template #cell-is_active="{ value }">
          <span :class="['users-screen__status', { 'users-screen__status--active': value }]">{{ value ? 'Yes' : 'No' }}</span>
        </template>
        <template #cell-created_at="{ value }">{{ formatDate(value) }}</template>
        <template #cell-updated_at="{ value }">{{ formatDate(value) }}</template>
      </VfDataTable>
    </div>

    <form id="users-user-form" v-else class="users-screen__form" novalidate @submit.prevent="saveUser">
      <VfCard>
        <VfTabs v-model="activeTab" :items="userFormTabs">
          <template #panel="{ activeValue }">
            <div v-if="activeValue === 'general'" class="users-screen__fields">
          <VfField class="users-screen__active-field" label="Active">
            <template #default="{ controlId }"><VfCheckbox :id="controlId" v-model="user.is_active" :disabled="saving" /></template>
          </VfField>
          <VfField label="Username" :error="firstError('username')" required>
            <template #default="{ controlId, describedBy, invalid }"><VfInput :id="controlId" v-model="user.username" :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" required /></template>
          </VfField>
          <VfField label="Email" :error="firstError('email')" required>
            <template #default="{ controlId, describedBy, invalid }"><VfInput :id="controlId" v-model="user.email" type="email" :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" required /></template>
          </VfField>
          <VfField label="Password" :description="editing ? 'Leave empty to keep the current password.' : 'At least 8 characters.'" :error="firstError('password')" :required="!editing">
            <template #default="{ controlId, describedBy, invalid }"><VfInput :id="controlId" v-model="user.password" type="password" :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" :required="!editing" /></template>
          </VfField>
          <VfField label="Confirm password" :error="firstError('password_confirmation')" :required="!editing">
            <template #default="{ controlId, describedBy, invalid }"><VfInput :id="controlId" v-model="user.password_confirmation" type="password" :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" :required="!editing" /></template>
          </VfField>
            </div>
          </template>
        </VfTabs>
      </VfCard>
    </form>
  </div>
</template>

<style scoped>
.users-screen { display: grid; gap: var(--vf-section-gap); }
.users-screen__column-select-all { display: flex; padding: 0.25rem 0 0.5rem; border-block-end: 1px solid var(--vf-color-border); }
.users-screen__status { color: var(--vf-color-muted); }
.users-screen__status--active { color: var(--vf-color-success); }
.users-screen__user-link { color: var(--vf-color-text-link); text-decoration: none; }
.users-screen__user-link:hover { text-decoration: underline; }
.users-screen__fields { display: grid; gap: var(--vf-section-gap); width: 100%; }
.users-screen__fields :deep(.vf-field) { width: 100%; }

@media (min-width: 1200px) {
  .users-screen__fields { width: 70%; max-width: 64rem; margin-inline: auto; }
  .users-screen__fields :deep(.vf-field) { grid-template-columns: 10rem minmax(0, 1fr); column-gap: var(--vf-section-gap); align-items: start; }
  .users-screen__fields :deep(.vf-field__label) { align-self: start; justify-self: end; padding-block-start: 0.65rem; text-align: end; }
  .users-screen__fields :deep(.vf-field__control),
  .users-screen__fields :deep(.vf-field__description),
  .users-screen__fields :deep(.vf-field__error) { grid-column: 2; }
  .users-screen__fields :deep(.vf-field__control) { grid-row: 1; }
  .users-screen__active-field :deep(.vf-field__label) { align-self: center; padding-block-start: 0; }
  .users-screen__fields > :deep(.vf-field) { grid-column: 1 / -1; }
}
</style>
