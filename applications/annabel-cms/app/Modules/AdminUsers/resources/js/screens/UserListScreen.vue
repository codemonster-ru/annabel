<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { CmAlert } from '@codemonster-ru/ui-vue';
import { CmButton } from '@codemonster-ru/ui-vue';
import { CmCard } from '@codemonster-ru/ui-vue';
import { CmCheckbox } from '@codemonster-ru/ui-vue';
import { VfConfirmDialog } from '@codemonster-ru/vueforge-core/confirm-dialog';
import { CmDataTable } from '@codemonster-ru/ui-vue';
import { VfDataTableColumnChooser } from '@codemonster-ru/vueforge-core/data-table-column-chooser';
import { CmDatePicker } from '@codemonster-ru/ui-vue';
import { CmDropdown } from '@codemonster-ru/ui-vue';
import { CmField } from '@codemonster-ru/ui-vue';
import { VfFormLayout } from '@codemonster-ru/vueforge-core/form-layout';
import { VfIconButton } from '@codemonster-ru/vueforge-core/icon-button';
import { CmInput } from '@codemonster-ru/ui-vue';
import { VfMenuItem } from '@codemonster-ru/vueforge-core/menu';
import { CmMenu } from '@codemonster-ru/ui-vue';
import { CmTabs } from '@codemonster-ru/ui-vue';
import { icons } from '@codemonster-ru/vueforge-icons';
import {
  formatDateTime,
  localDateTimeValueToIso,
  toLocalDateTimeValue,
} from '../../../../Admin/resources/js/support/dateTime';

const props = defineProps({ user: { type: Object, default: null } });

const columns = [
  { key: 'actions', header: 'Actions', width: '1%', align: 'center', verticalAlign: 'middle' },
  { key: 'id', header: 'ID', width: '1%', align: 'center', verticalAlign: 'middle' },
  { key: 'username', header: 'Username', verticalAlign: 'middle' },
  { key: 'email', header: 'Email', verticalAlign: 'middle' },
  { key: 'is_active', header: 'Active', verticalAlign: 'middle' },
  { key: 'created_at', header: 'Created', verticalAlign: 'middle' },
  { key: 'updated_at', header: 'Updated', verticalAlign: 'middle' },
];
const roleColumns = [
  { key: 'selected', header: '', width: '1%', align: 'center', verticalAlign: 'middle' },
  { key: 'id', header: 'ID', width: '1%', align: 'center', verticalAlign: 'middle' },
  { key: 'name', header: 'Role', width: '25%', verticalAlign: 'middle' },
  { key: 'period', header: 'Active period', verticalAlign: 'top' },
];
const userFormTabs = [
  { value: 'general', label: 'General' },
  { value: 'roles', label: 'Roles' },
];
const emptyUser = () => ({ id: null, username: '', email: '', password: '', password_confirmation: '', is_active: true, active_from: '', active_until: '' });
const roleFromPayload = (value) => ({
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
const roles = ref([]);
const visibleColumns = ref(columns.map((column) => column.key));
const page = ref(1);
const pageSize = ref(10);
const totalRows = ref(0);
const csrfToken = ref('');
const loading = ref(true);
const saving = ref(false);
const deleting = ref(false);
const deleteCandidate = ref(null);
const preferencesSaving = ref(false);
const error = ref('');
const success = ref('');
const errors = ref({});
const activeTab = ref('general');
const activeFromMax = computed(() => shiftLocalDateTime(user.value.active_until, -1));
const activeUntilMin = computed(() => shiftLocalDateTime(user.value.active_from, 1));
const can = (permission) => props.user?.roles?.includes('admin') || props.user?.permissions?.includes(permission);

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

function membershipStartMax(role) {
  return shiftLocalDateTime(role.active_until, -1);
}

function membershipEndMin(role) {
  return shiftLocalDateTime(role.active_from, 1);
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
    roles.value = Array.isArray(payload.roles) ? payload.roles.map(roleFromPayload) : [];
    csrfToken.value = payload.csrf_token || '';
  } catch (exception) {
    error.value = exception instanceof Error ? exception.message : 'Unable to load user.';
  } finally {
    loading.value = false;
  }
}

async function loadRoleOptions() {
  loading.value = true;
  error.value = '';
  try {
    const response = await fetch('/admin/users/role-options', { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(payload.message || 'Unable to load roles.');
    roles.value = Array.isArray(payload.roles) ? payload.roles.map(roleFromPayload) : [];
    csrfToken.value = payload.csrf_token || '';
  } catch (exception) {
    error.value = exception instanceof Error ? exception.message : 'Unable to load roles.';
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
  body.append('roles', JSON.stringify(roles.value.filter((role) => role.selected).map((role) => ({
    id: role.id,
    active_from: localDateTimeValueToIso(role.active_from) ?? role.active_from,
    active_until: localDateTimeValueToIso(role.active_until) ?? role.active_until,
  }))));
  const endpoint = editing.value ? `/admin/users/data/${user.value.id}` : '/admin/users/data';

  try {
    const response = await fetch(endpoint, { method: 'POST', headers: { Accept: 'application/json' }, body, credentials: 'same-origin' });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
      if (response.status === 422) {
        errors.value = payload.errors || {};
        if (Object.keys(errors.value).some((field) => field === 'roles' || field.startsWith('roles.'))) activeTab.value = 'roles';
      }
      throw new Error(payload.message || 'Unable to save user.');
    }
    user.value = userFromPayload(payload.user || {});
    roles.value = Array.isArray(payload.roles) ? payload.roles.map(roleFromPayload) : roles.value;
    success.value = payload.message || 'User saved successfully.';
    if (!editing.value) window.location.assign('/admin/users');
  } catch (exception) {
    error.value = exception instanceof Error ? exception.message : 'Unable to save user.';
  } finally {
    saving.value = false;
  }
}

async function deleteUser(value) {
  if (!value?.id || deleting.value) return;
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
onMounted(() => (formMode.value ? (editId.value ? loadUser() : loadRoleOptions()) : loadUsers()));
</script>

<template>
  <div class="users-screen">
    <VfConfirmDialog
      :open="Boolean(deleteCandidate)"
      title="Delete user?"
      :description="deleteCandidate ? `The user “${deleteCandidate.username}” will be deleted.` : ''"
      confirm-label="Delete"
      confirm-variant="danger"
      :loading="deleting"
      :disabled="deleting"
      :close-on-confirm="false"
      @update:open="deleteCandidate = $event ? deleteCandidate : null"
      @confirm="deleteUser(deleteCandidate)"
    />
    <Teleport v-if="formMode" to="#admin-page-actions">
      <CmButton type="submit" form="users-user-form" :loading="saving" :disabled="loading || deleting">{{ saving ? 'Saving...' : 'Save user' }}</CmButton>
      <CmButton variant="secondary" :disabled="saving || deleting" @click="backToUsers">Back</CmButton>
    </Teleport>
    <Teleport v-else-if="can('users.create')" to="#admin-page-actions">
      <CmButton variant="primary" :disabled="loading || saving" @click="newUser">New user</CmButton>
    </Teleport>

    <CmAlert v-if="error" tone="danger" title="Users">
      {{ error }}
    </CmAlert>
    <CmAlert v-if="success" tone="success" title="Users">
      {{ success }}
    </CmAlert>

    <div v-if="!formMode" class="users-screen__list">
      <CmDataTable
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
          <VfDataTableColumnChooser
            :columns="columns"
            :model-value="visibleColumns"
            :required-column-keys="['actions']"
            :disabled="preferencesSaving"
            @update:model-value="saveColumnPreferences"
          />
        </template>
        <template #cell-actions="{ row }">
          <CmDropdown v-if="can('users.update') || can('users.delete')" placement="bottom-start">
            <template #trigger>
              <VfIconButton :icon="icons.bars" variant="ghost" size="sm" :aria-label="`Actions for ${row.username}`" :title="`Actions for ${row.username}`" />
            </template>
            <CmMenu>
              <VfMenuItem v-if="can('users.update')" label="Edit" :icon="icons.pencil" @select="editUser(row)" />
              <VfMenuItem v-if="can('users.delete')" label="Delete" :icon="icons.trash" tone="danger" @select="deleteCandidate = row" />
            </CmMenu>
          </CmDropdown>
        </template>
        <template #cell-username="{ row }">
          <a v-if="can('users.update')" class="users-screen__user-link" :href="`/admin/users/${row.id}/edit`">{{ row.username }}</a>
          <span v-else>{{ row.username }}</span>
        </template>
        <template #cell-is_active="{ value }">
          <span :class="['users-screen__status', { 'users-screen__status--active': value }]">{{ value ? 'Yes' : 'No' }}</span>
        </template>
        <template #cell-created_at="{ value }">{{ formatDateTime(value) }}</template>
        <template #cell-updated_at="{ value }">{{ formatDateTime(value) }}</template>
      </CmDataTable>
    </div>

    <form id="users-user-form" v-else class="users-screen__form" novalidate @submit.prevent="saveUser">
      <CmCard>
        <CmTabs v-model="activeTab" :items="userFormTabs">
          <template #panel="{ activeValue }">
            <VfFormLayout v-if="activeValue === 'general'" mode="responsive" label-width="minmax(14rem, 25%)">
          <CmField class="users-screen__active-field" label="Active">
            <template #default="{ controlId }"><CmCheckbox :id="controlId" v-model="user.is_active" :disabled="saving" /></template>
          </CmField>
          <CmField class="users-screen__activity-field" label="Active from" :error="firstError('active_from')">
            <template #default="{ controlId, describedBy, invalid }"><CmDatePicker :id="controlId" v-model="user.active_from" :max="activeFromMax" show-time clearable :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" /></template>
          </CmField>
          <CmField class="users-screen__activity-field" label="Active until" :error="firstError('active_until')">
            <template #default="{ controlId, describedBy, invalid }"><CmDatePicker :id="controlId" v-model="user.active_until" :min="activeUntilMin" show-time clearable :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" /></template>
          </CmField>
          <CmField label="Username" :error="firstError('username')" required>
            <template #default="{ controlId, describedBy, invalid }"><CmInput :id="controlId" v-model="user.username" :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" required /></template>
          </CmField>
          <CmField label="Email" :error="firstError('email')" required>
            <template #default="{ controlId, describedBy, invalid }"><CmInput :id="controlId" v-model="user.email" type="email" :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" required /></template>
          </CmField>
          <CmField label="New password" :error="firstError('password')" :required="!editing">
            <template #default="{ controlId, describedBy, invalid }"><CmInput :id="controlId" v-model="user.password" type="password" :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" :required="!editing" /></template>
          </CmField>
          <CmField label="Confirm new password" :error="firstError('password_confirmation')" :required="!editing">
            <template #default="{ controlId, describedBy, invalid }"><CmInput :id="controlId" v-model="user.password_confirmation" type="password" :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" :required="!editing" /></template>
          </CmField>
            </VfFormLayout>
            <div v-else-if="activeValue === 'roles'" class="users-screen__roles">
              <CmAlert v-if="firstError('roles')" tone="danger" title="Roles">{{ firstError('roles') }}</CmAlert>
              <CmDataTable :columns="roleColumns" :rows="roles" row-key="id" striped column-dividers empty-text="No roles available">
                <template #cell-selected="{ row: role }">
                  <CmCheckbox v-model="role.selected" :aria-label="`Select role ${role.name}`" :disabled="saving" />
                </template>
                <template #cell-id="{ value }">
                  <span class="users-screen__role-id">{{ value }}</span>
                </template>
                <template #cell-name="{ row: role }">
                  <div class="users-screen__role-name">
                    <a class="users-screen__role-link" :href="`/admin/roles/${role.id}/edit`">{{ role.name }}</a>
                    <span v-if="!role.is_active" class="users-screen__role-inactive">Inactive</span>
                  </div>
                </template>
                <template #cell-period="{ row: role }">
                  <div class="users-screen__role-period">
                    <CmField :error="firstError(`roles.${role.id}.active_from`)">
                      <template #default="{ controlId, describedBy, invalid }">
                        <CmDatePicker :id="controlId" v-model="role.active_from" :max="membershipStartMax(role)" placeholder="From" aria-label="Membership starts at" size="sm" show-time clearable :aria-describedby="describedBy" :invalid="invalid" :disabled="saving || !role.selected" />
                      </template>
                    </CmField>
                    <span class="users-screen__role-period-separator" aria-hidden="true">—</span>
                    <CmField :error="firstError(`roles.${role.id}.active_until`)">
                      <template #default="{ controlId, describedBy, invalid }">
                        <CmDatePicker :id="controlId" v-model="role.active_until" :min="membershipEndMin(role)" placeholder="Until" aria-label="Membership ends at" size="sm" show-time clearable :aria-describedby="describedBy" :invalid="invalid" :disabled="saving || !role.selected" />
                      </template>
                    </CmField>
                  </div>
                </template>
              </CmDataTable>
            </div>
          </template>
        </CmTabs>
      </CmCard>
    </form>
  </div>
</template>

<style scoped>
.users-screen { display: grid; gap: var(--vf-section-gap); }
.users-screen__status { color: var(--vf-color-muted); }
.users-screen__status--active { color: var(--vf-color-success); }
.users-screen__user-link { color: var(--vf-color-text-link); text-decoration: none; }
.users-screen__user-link:hover { text-decoration: underline; }
.users-screen__roles { display: grid; gap: var(--vf-section-gap); width: 100%; }
.users-screen__role-id { color: var(--vf-color-muted); font-variant-numeric: tabular-nums; }
.users-screen__role-name { display: grid; gap: 0.125rem; }
.users-screen__role-link { color: var(--vf-color-text-link); text-decoration: none; }
.users-screen__role-link:hover { text-decoration: underline; }
.users-screen__role-inactive { color: var(--vf-color-muted); font-size: 0.875rem; }
.users-screen__role-period { display: grid; gap: var(--vf-section-gap); }
.users-screen__role-period :deep(.vf-field) { width: 100%; }
.users-screen__role-period-separator { display: none; color: var(--vf-color-muted); }

@media (min-width: 768px) {
  .users-screen__active-field :deep(.vf-field__label) { align-self: center; padding-block-start: 0; }
}

@media (min-width: 1200px) {
  .users-screen__role-period { grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr); align-items: center; }
  .users-screen__role-period-separator { display: block; }
}
</style>
