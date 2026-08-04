<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { VfAlert } from '@codemonster-ru/vueforge-core/alert';
import { VfButton } from '@codemonster-ru/vueforge-core/button';
import { VfCard } from '@codemonster-ru/vueforge-core/card';
import { VfCheckbox } from '@codemonster-ru/vueforge-core/checkbox';
import { VfDataTable } from '@codemonster-ru/vueforge-core/data-table';
import { VfDatePicker } from '@codemonster-ru/vueforge-core/date-picker';
import { VfDropdown } from '@codemonster-ru/vueforge-core/dropdown';
import { VfField } from '@codemonster-ru/vueforge-core/field';
import { VfIconButton } from '@codemonster-ru/vueforge-core/icon-button';
import { VfInput } from '@codemonster-ru/vueforge-core/input';
import { VfMenu, VfMenuItem } from '@codemonster-ru/vueforge-core/menu';
import { VfTabs } from '@codemonster-ru/vueforge-core/tabs';
import { icons } from '@codemonster-ru/vueforge-icons';
import {
  formatDateTime,
  localDateTimeValueToIso,
  toLocalDateTimeValue,
} from '../../../../Admin/resources/js/support/dateTime';

const columns = [
  { key: 'actions', header: '', width: '1%', align: 'center', verticalAlign: 'middle' },
  { key: 'id', header: 'ID', width: '1%', align: 'center', verticalAlign: 'middle' },
  { key: 'username', header: 'Username', verticalAlign: 'middle' },
  { key: 'email', header: 'Email', verticalAlign: 'middle' },
  { key: 'is_active', header: 'Active', verticalAlign: 'middle' },
  { key: 'created_at', header: 'Created', verticalAlign: 'middle' },
  { key: 'updated_at', header: 'Updated', verticalAlign: 'middle' },
];
const groupColumns = [
  { key: 'selected', header: '', width: '1%', align: 'center', verticalAlign: 'middle' },
  { key: 'id', header: 'ID', width: '1%', align: 'center', verticalAlign: 'middle' },
  { key: 'name', header: 'Group', width: '25%', verticalAlign: 'middle' },
  { key: 'period', header: 'Active period', verticalAlign: 'top' },
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
const userFormTabs = [
  { value: 'general', label: 'General' },
  { value: 'groups', label: 'Groups' },
];
const emptyUser = () => ({ id: null, username: '', email: '', password: '', password_confirmation: '', is_active: true, active_from: '', active_until: '' });
const groupFromPayload = (value) => ({
  ...value,
  selected: Boolean(value?.selected),
  active_from: toLocalDateTimeValue(value?.active_from),
  active_until: toLocalDateTimeValue(value?.active_until),
});

const currentPath = typeof window !== 'undefined' ? window.location.pathname : '/admin/users';
const formMode = computed(() => currentPath === '/admin/users/create' || /^\/admin\/users\/\d+\/edit$/.test(currentPath));
const editId = computed(() => currentPath.match(/^\/admin\/users\/(\d+)\/edit$/)?.[1] || null);
const editing = computed(() => editId.value !== null);
const rows = ref([]);
const user = ref(emptyUser());
const groups = ref([]);
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
const activeFromMax = computed(() => shiftLocalDateTime(user.value.active_until, -1));
const activeUntilMin = computed(() => shiftLocalDateTime(user.value.active_from, 1));

function firstError(field) {
  const messages = errors.value[field];
  return Array.isArray(messages) && messages.length > 0 ? messages[0] : '';
}

function shiftLocalDateTime(value, minutes) {
  if (!value) return undefined;

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return undefined;

  date.setMinutes(date.getMinutes() + minutes);

  return toLocalDateTimeValue(date);
}

function userFromPayload(value) {
  return {
    ...emptyUser(),
    ...value,
    active_from: toLocalDateTimeValue(value?.active_from),
    active_until: toLocalDateTimeValue(value?.active_until),
  };
}

function membershipStartMax(group) {
  return shiftLocalDateTime(group.active_until, -1);
}

function membershipEndMin(group) {
  return shiftLocalDateTime(group.active_from, 1);
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
    user.value = userFromPayload(payload.user || {});
    groups.value = Array.isArray(payload.groups) ? payload.groups.map(groupFromPayload) : [];
    csrfToken.value = payload.csrf_token || '';
  } catch (exception) {
    error.value = exception instanceof Error ? exception.message : 'Unable to load user.';
  } finally {
    loading.value = false;
  }
}

async function loadGroupOptions() {
  loading.value = true;
  error.value = '';
  try {
    const response = await fetch('/admin/users/group-options', { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(payload.message || 'Unable to load groups.');
    groups.value = Array.isArray(payload.groups) ? payload.groups.map(groupFromPayload) : [];
    csrfToken.value = payload.csrf_token || '';
  } catch (exception) {
    error.value = exception instanceof Error ? exception.message : 'Unable to load groups.';
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
  body.append('active_from', localDateTimeValueToIso(user.value.active_from) ?? user.value.active_from);
  body.append('active_until', localDateTimeValueToIso(user.value.active_until) ?? user.value.active_until);
  body.append('groups', JSON.stringify(groups.value.filter((group) => group.selected).map((group) => ({
    id: group.id,
    active_from: localDateTimeValueToIso(group.active_from) ?? group.active_from,
    active_until: localDateTimeValueToIso(group.active_until) ?? group.active_until,
  }))));
  const endpoint = editing.value ? `/admin/users/data/${user.value.id}` : '/admin/users/data';

  try {
    const response = await fetch(endpoint, { method: 'POST', headers: { Accept: 'application/json' }, body, credentials: 'same-origin' });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
      if (response.status === 422) {
        errors.value = payload.errors || {};
        if (Object.keys(errors.value).some((field) => field === 'groups' || field.startsWith('groups.'))) activeTab.value = 'groups';
      }
      throw new Error(payload.message || 'Unable to save user.');
    }
    user.value = userFromPayload(payload.user || {});
    groups.value = Array.isArray(payload.groups) ? payload.groups.map(groupFromPayload) : groups.value;
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
onMounted(() => (formMode.value ? (editId.value ? loadUser() : loadGroupOptions()) : loadUsers()));
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
          <a class="users-screen__user-link" :href="`/admin/users/${row.id}/edit`">{{ row.username }}</a>
        </template>
        <template #cell-is_active="{ value }">
          <span :class="['users-screen__status', { 'users-screen__status--active': value }]">{{ value ? 'Yes' : 'No' }}</span>
        </template>
        <template #cell-created_at="{ value }">{{ formatDateTime(value) }}</template>
        <template #cell-updated_at="{ value }">{{ formatDateTime(value) }}</template>
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
          <VfField class="users-screen__activity-field" label="Active from" :error="firstError('active_from')">
            <template #default="{ controlId, describedBy, invalid }"><VfDatePicker :id="controlId" v-model="user.active_from" :max="activeFromMax" show-time clearable :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" /></template>
          </VfField>
          <VfField class="users-screen__activity-field" label="Active until" :error="firstError('active_until')">
            <template #default="{ controlId, describedBy, invalid }"><VfDatePicker :id="controlId" v-model="user.active_until" :min="activeUntilMin" show-time clearable :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" /></template>
          </VfField>
          <VfField label="Username" :error="firstError('username')" required>
            <template #default="{ controlId, describedBy, invalid }"><VfInput :id="controlId" v-model="user.username" :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" required /></template>
          </VfField>
          <VfField label="Email" :error="firstError('email')" required>
            <template #default="{ controlId, describedBy, invalid }"><VfInput :id="controlId" v-model="user.email" type="email" :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" required /></template>
          </VfField>
          <VfField label="New password" :error="firstError('password')" :required="!editing">
            <template #default="{ controlId, describedBy, invalid }"><VfInput :id="controlId" v-model="user.password" type="password" :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" :required="!editing" /></template>
          </VfField>
          <VfField label="Confirm new password" :error="firstError('password_confirmation')" :required="!editing">
            <template #default="{ controlId, describedBy, invalid }"><VfInput :id="controlId" v-model="user.password_confirmation" type="password" :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" :required="!editing" /></template>
          </VfField>
            </div>
            <div v-else-if="activeValue === 'groups'" class="users-screen__groups">
              <VfAlert v-if="firstError('groups')" tone="danger" title="Groups">{{ firstError('groups') }}</VfAlert>
              <VfDataTable :columns="groupColumns" :rows="groups" row-key="id" striped column-dividers empty-text="No groups available">
                <template #cell-selected="{ row: group }">
                  <VfCheckbox v-model="group.selected" :aria-label="`Select group ${group.name}`" :disabled="saving" />
                </template>
                <template #cell-id="{ value }">
                  <span class="users-screen__group-id">{{ value }}</span>
                </template>
                <template #cell-name="{ row: group }">
                  <div class="users-screen__group-name">
                    <a class="users-screen__group-link" :href="`/admin/groups/${group.id}/edit`">{{ group.name }}</a>
                    <span v-if="!group.is_active" class="users-screen__group-inactive">Inactive</span>
                  </div>
                </template>
                <template #cell-period="{ row: group }">
                  <div class="users-screen__group-period">
                    <VfField :error="firstError(`groups.${group.id}.active_from`)">
                      <template #default="{ controlId, describedBy, invalid }">
                        <VfDatePicker :id="controlId" v-model="group.active_from" :max="membershipStartMax(group)" placeholder="From" aria-label="Membership starts at" size="sm" show-time clearable :aria-describedby="describedBy" :invalid="invalid" :disabled="saving || !group.selected" />
                      </template>
                    </VfField>
                    <span class="users-screen__group-period-separator" aria-hidden="true">—</span>
                    <VfField :error="firstError(`groups.${group.id}.active_until`)">
                      <template #default="{ controlId, describedBy, invalid }">
                        <VfDatePicker :id="controlId" v-model="group.active_until" :min="membershipEndMin(group)" placeholder="Until" aria-label="Membership ends at" size="sm" show-time clearable :aria-describedby="describedBy" :invalid="invalid" :disabled="saving || !group.selected" />
                      </template>
                    </VfField>
                  </div>
                </template>
              </VfDataTable>
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
.users-screen__groups { display: grid; gap: var(--vf-section-gap); width: 100%; }
.users-screen__group-id { color: var(--vf-color-muted); font-variant-numeric: tabular-nums; }
.users-screen__group-name { display: grid; gap: 0.125rem; }
.users-screen__group-link { color: var(--vf-color-text-link); text-decoration: none; }
.users-screen__group-link:hover { text-decoration: underline; }
.users-screen__group-inactive { color: var(--vf-color-muted); font-size: 0.875rem; }
.users-screen__group-period { display: grid; gap: var(--vf-section-gap); }
.users-screen__group-period :deep(.vf-field) { width: 100%; }
.users-screen__group-period-separator { display: none; color: var(--vf-color-muted); }

@media (min-width: 1200px) {
  .users-screen__fields :deep(.vf-field) { grid-template-columns: minmax(14rem, 25%) minmax(0, 1fr); column-gap: var(--vf-section-gap); align-items: start; }
  .users-screen__fields :deep(.vf-field__label) { align-self: start; justify-self: end; padding-block-start: 0.65rem; overflow-wrap: anywhere; text-align: end; }
  .users-screen__fields :deep(.vf-field__control),
  .users-screen__fields :deep(.vf-field__description),
  .users-screen__fields :deep(.vf-field__error) { grid-column: 2; }
  .users-screen__fields :deep(.vf-field__control) { grid-row: 1; }
  .users-screen__active-field :deep(.vf-field__label) { align-self: center; padding-block-start: 0; }
  .users-screen__fields > :deep(.vf-field) { grid-column: 1 / -1; }
  .users-screen__group-period { grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr); align-items: center; }
  .users-screen__group-period-separator { display: block; }
}
</style>
