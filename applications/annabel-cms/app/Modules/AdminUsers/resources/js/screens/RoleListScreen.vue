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
import { VfTextarea } from '@codemonster-ru/vueforge-core/textarea';
import { icons } from '@codemonster-ru/vueforge-icons';
import {
  formatDateTime,
  localDateTimeValueToIso,
  toLocalDateTimeValue,
} from '../../../../Admin/resources/js/support/dateTime';

const props = defineProps({ user: { type: Object, default: null } });

const columns = [
  { key: 'actions', header: '', width: '1%', align: 'center', verticalAlign: 'middle' },
  { key: 'id', header: 'ID', width: '1%', align: 'center', verticalAlign: 'middle' },
  { key: 'name', header: 'Name', verticalAlign: 'middle' },
  { key: 'code', header: 'Code', verticalAlign: 'middle' },
  { key: 'is_active', header: 'Active', verticalAlign: 'middle' },
  { key: 'sort_order', header: 'Sort order', verticalAlign: 'middle' },
  { key: 'created_at', header: 'Created', verticalAlign: 'middle' },
  { key: 'updated_at', header: 'Updated', verticalAlign: 'middle' },
];
const columnLabels = { actions: 'Actions', id: 'ID', name: 'Name', code: 'Code', is_active: 'Active', sort_order: 'Sort order', created_at: 'Created', updated_at: 'Updated' };
const roleFormTabs = [{ value: 'general', label: 'General' }, { value: 'permissions', label: 'Permissions' }];
const emptyRole = () => ({ id: null, name: '', code: '', description: '', is_active: true, active_from: '', active_until: '', sort_order: 1 });
const currentPath = typeof window !== 'undefined' ? window.location.pathname : '/admin/roles';
const formMode = computed(() => currentPath === '/admin/roles/create' || /^\/admin\/roles\/\d+\/edit$/.test(currentPath));
const editId = computed(() => currentPath.match(/^\/admin\/roles\/(\d+)\/edit$/)?.[1] || null);
const editing = computed(() => editId.value !== null);
const rows = ref([]); const role = ref(emptyRole()); const permissions = ref([]); const visibleColumns = ref(columns.map((column) => column.key));
const page = ref(1); const pageSize = ref(10); const totalRows = ref(0); const csrfToken = ref('');
const loading = ref(true); const saving = ref(false); const deleting = ref(false); const preferencesSaving = ref(false);
const error = ref(''); const success = ref(''); const errors = ref({});
const activeTab = ref('general');
const activeFromMax = computed(() => shiftLocalDateTime(role.value.active_until, -1));
const activeUntilMin = computed(() => shiftLocalDateTime(role.value.active_from, 1));
const can = (permission) => props.user?.roles?.includes('admin') || props.user?.permissions?.includes(permission);
const permissionGroups = computed(() => {
  const groups = new Map();
  permissions.value.forEach((permission) => {
    const category = permission.category || 'Other';
    if (!groups.has(category)) groups.set(category, []);
    groups.get(category).push(permission);
  });
  return Array.from(groups, ([category, items]) => ({ category, items }));
});

function firstError(field) { const messages = errors.value[field]; return Array.isArray(messages) && messages.length ? messages[0] : ''; }
function shiftLocalDateTime(value, minutes) {
  if (!value) return undefined;

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return undefined;

  date.setMinutes(date.getMinutes() + minutes);

  return toLocalDateTimeValue(date);
}
function roleFromPayload(value) {
  return {
    ...emptyRole(),
    ...value,
    active_from: toLocalDateTimeValue(value?.active_from),
    active_until: toLocalDateTimeValue(value?.active_until),
  };
}
async function loadRoles() {
  loading.value = true; error.value = '';
  try {
    const query = new URLSearchParams({ page: String(page.value), per_page: String(pageSize.value) });
    const response = await fetch(`/admin/roles/data?${query}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(payload.message || 'Unable to load roles.');
    rows.value = Array.isArray(payload.data) ? payload.data : []; totalRows.value = Number(payload.total) || 0; csrfToken.value = payload.csrf_token || '';
    if (Array.isArray(payload.visible_columns) && payload.visible_columns.includes('actions')) visibleColumns.value = payload.visible_columns;
  } catch (exception) { error.value = exception instanceof Error ? exception.message : 'Unable to load roles.'; } finally { loading.value = false; }
}
async function loadRole() {
  loading.value = true; error.value = '';
  try {
    const response = await fetch(`/admin/roles/data/${editId.value}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
    const payload = await response.json().catch(() => ({})); if (!response.ok) throw new Error(payload.message || 'Unable to load role.');
    role.value = roleFromPayload(payload.role || {}); permissions.value = Array.isArray(payload.permissions) ? payload.permissions : []; csrfToken.value = payload.csrf_token || '';
  } catch (exception) { error.value = exception instanceof Error ? exception.message : 'Unable to load role.'; } finally { loading.value = false; }
}
async function loadPermissionOptions() {
  loading.value = true; error.value = '';
  try {
    const response = await fetch('/admin/roles/permission-options', { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
    const payload = await response.json().catch(() => ({})); if (!response.ok) throw new Error(payload.message || 'Unable to load permissions.');
    permissions.value = Array.isArray(payload.permissions) ? payload.permissions : []; csrfToken.value = payload.csrf_token || '';
  } catch (exception) { error.value = exception instanceof Error ? exception.message : 'Unable to load permissions.'; } finally { loading.value = false; }
}
async function saveColumnPreferences(next) {
  const previous = visibleColumns.value; visibleColumns.value = columns.map((column) => column.key).filter((key) => next.includes(key)); preferencesSaving.value = true;
  const body = new FormData(); body.append('_token', csrfToken.value); body.append('visible_columns', JSON.stringify(visibleColumns.value));
  try { const response = await fetch('/admin/roles/preferences', { method: 'POST', headers: { Accept: 'application/json' }, body, credentials: 'same-origin' }); if (!response.ok) throw new Error('Unable to save column preferences.'); }
  catch (exception) { visibleColumns.value = previous; error.value = exception instanceof Error ? exception.message : 'Unable to save column preferences.'; } finally { preferencesSaving.value = false; }
}
function toggleColumn(key, value) { if (key !== 'actions') saveColumnPreferences(value ? [...visibleColumns.value, key] : visibleColumns.value.filter((column) => column !== key)); }
function toggleAllColumns(value) { saveColumnPreferences(value ? columns.map((column) => column.key) : ['actions']); }
function editRole(value) { window.location.assign(`/admin/roles/${value.id}/edit`); }
function newRole() { window.location.assign('/admin/roles/create'); }
function backToRoles() { window.location.assign('/admin/roles'); }
async function saveRole() {
  if (saving.value) return; saving.value = true; error.value = ''; success.value = ''; errors.value = {};
  const body = new FormData(); body.append('_token', csrfToken.value); body.append('name', role.value.name); body.append('code', role.value.code); body.append('description', role.value.description || ''); body.append('is_active', role.value.is_active ? '1' : '0'); body.append('active_from', localDateTimeValueToIso(role.value.active_from) ?? role.value.active_from); body.append('active_until', localDateTimeValueToIso(role.value.active_until) ?? role.value.active_until); body.append('sort_order', String(role.value.sort_order ?? 1)); body.append('permissions', JSON.stringify(permissions.value.filter((permission) => permission.selected).map((permission) => permission.code)));
  const endpoint = editing.value ? `/admin/roles/data/${role.value.id}` : '/admin/roles/data';
  try {
    const response = await fetch(endpoint, { method: 'POST', headers: { Accept: 'application/json' }, body, credentials: 'same-origin' }); const payload = await response.json().catch(() => ({}));
    if (!response.ok) { if (response.status === 422) errors.value = payload.errors || {}; throw new Error(payload.message || 'Unable to save role.'); }
    role.value = roleFromPayload(payload.role || {}); permissions.value = Array.isArray(payload.permissions) ? payload.permissions : permissions.value; success.value = payload.message || 'Role saved successfully.'; if (!editing.value) window.location.assign('/admin/roles');
  } catch (exception) { error.value = exception instanceof Error ? exception.message : 'Unable to save role.'; } finally { saving.value = false; }
}
async function deleteRole(value) {
  if (!value?.id || deleting.value || !window.confirm('Delete this role?')) return; deleting.value = true;
  const body = new FormData(); body.append('_token', csrfToken.value);
  try { const response = await fetch(`/admin/roles/data/${value.id}/delete`, { method: 'POST', headers: { Accept: 'application/json' }, body, credentials: 'same-origin' }); const payload = await response.json().catch(() => ({})); if (!response.ok) throw new Error(payload.message || 'Unable to delete role.'); window.location.assign('/admin/roles'); }
  catch (exception) { error.value = exception instanceof Error ? exception.message : 'Unable to delete role.'; } finally { deleting.value = false; }
}
watch([page, pageSize], loadRoles); onMounted(() => (formMode.value ? (editId.value ? loadRole() : loadPermissionOptions()) : loadRoles()));
</script>

<template>
  <div class="roles-screen">
    <Teleport v-if="formMode" to="#admin-page-actions"><VfButton type="submit" form="roles-role-form" :loading="saving" :disabled="loading || deleting">{{ saving ? 'Saving...' : 'Save role' }}</VfButton><VfButton variant="secondary" :disabled="saving || deleting" @click="backToRoles">Back</VfButton></Teleport>
    <Teleport v-else-if="can('roles.create')" to="#admin-page-actions"><VfButton variant="primary" :disabled="loading || saving" @click="newRole">New role</VfButton></Teleport>
    <VfAlert v-if="error" tone="danger" title="Roles">{{ error }}</VfAlert><VfAlert v-if="success" tone="success" title="Roles">{{ success }}</VfAlert>
    <div v-if="!formMode" class="roles-screen__list">
      <VfDataTable :columns="columns" :visible-column-keys="visibleColumns" :rows="rows" row-key="id" striped column-dividers :loading="loading" pagination pagination-mode="manual" :page="page" :page-size="pageSize" :total-rows="totalRows" empty-text="No roles found" @update:page="page = $event" @update:page-size="pageSize = $event">
        <template #header-actions><VfDropdown placement="bottom-start" :close-on-select="false"><template #trigger><VfIconButton :icon="icons.gear" variant="ghost" size="sm" aria-label="Configure columns" title="Configure columns" :disabled="preferencesSaving" /></template><div class="roles-screen__column-select-all"><VfCheckbox label="All columns" :model-value="visibleColumns.length === columns.length" :disabled="preferencesSaving" @update:model-value="toggleAllColumns" /></div><VfCheckbox v-for="column in columns" :key="column.key" :model-value="visibleColumns.includes(column.key)" :label="columnLabels[column.key]" :disabled="column.key === 'actions' || preferencesSaving" @update:model-value="toggleColumn(column.key, $event)" /></VfDropdown></template>
        <template #cell-actions="{ row }"><VfDropdown v-if="can('roles.update') || can('roles.delete')" placement="bottom-start"><template #trigger><VfIconButton :icon="icons.bars" variant="ghost" size="sm" aria-label="Actions" title="Actions" :disabled="deleting" /></template><VfMenu><VfMenuItem v-if="can('roles.update')" label="Edit" :icon="icons.pencil" @select="editRole(row)" /><VfMenuItem v-if="can('roles.delete')" label="Delete" :icon="icons.trash" tone="danger" @select="deleteRole(row)" /></VfMenu></VfDropdown></template>
        <template #cell-name="{ value, row }"><a v-if="can('roles.update')" class="roles-screen__role-link" :href="`/admin/roles/${row.id}/edit`">{{ value }}</a><span v-else>{{ value }}</span></template>
        <template #cell-code="{ value }">{{ value }}</template>
        <template #cell-is_active="{ value }"><span :class="['roles-screen__status', { 'roles-screen__status--active': value }]">{{ value ? 'Yes' : 'No' }}</span></template>
        <template #cell-sort_order="{ value }">{{ value }}</template>
        <template #cell-created_at="{ value }">{{ formatDateTime(value) }}</template><template #cell-updated_at="{ value }">{{ formatDateTime(value) }}</template>
      </VfDataTable>
    </div>
    <form id="roles-role-form" v-else class="roles-screen__form" novalidate @submit.prevent="saveRole"><VfCard><VfTabs v-model="activeTab" :items="roleFormTabs"><template #panel="{ activeValue }"><div v-if="activeValue === 'general'" class="roles-screen__fields"><VfField class="roles-screen__active-field" label="Active"><template #default="{ controlId }"><VfCheckbox :id="controlId" v-model="role.is_active" :disabled="saving" /></template></VfField><VfField class="roles-screen__activity-field" label="Active from" :error="firstError('active_from')"><template #default="{ controlId, describedBy, invalid }"><VfDatePicker :id="controlId" v-model="role.active_from" :max="activeFromMax" show-time clearable :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" /></template></VfField><VfField class="roles-screen__activity-field" label="Active until" :error="firstError('active_until')"><template #default="{ controlId, describedBy, invalid }"><VfDatePicker :id="controlId" v-model="role.active_until" :min="activeUntilMin" show-time clearable :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" /></template></VfField><VfField label="Name" :error="firstError('name')" required><template #default="{ controlId, describedBy, invalid }"><VfInput :id="controlId" v-model="role.name" :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" required /></template></VfField><VfField label="Code" :error="firstError('code')" required><template #default="{ controlId, describedBy, invalid }"><VfInput :id="controlId" v-model="role.code" :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" required /></template></VfField><VfField label="Description" :error="firstError('description')"><template #default="{ controlId, describedBy, invalid }"><VfTextarea :id="controlId" v-model="role.description" :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" maxlength="255" /></template></VfField><VfField label="Sort order" :error="firstError('sort_order')"><template #default="{ controlId, describedBy, invalid }"><VfInput :id="controlId" v-model="role.sort_order" type="number" min="1" max="1000000" :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" /></template></VfField></div><div v-else-if="activeValue === 'permissions'" class="roles-screen__permissions"><VfAlert v-if="permissions.some((permission) => permission.locked)" tone="info" title="System administrator">The Admin role always has every permission.</VfAlert><p v-if="firstError('permissions')" class="roles-screen__permissions-error">{{ firstError('permissions') }}</p><div class="roles-screen__permission-grid"><section v-for="permissionGroup in permissionGroups" :key="permissionGroup.category" class="roles-screen__permission-group"><h3>{{ permissionGroup.category }}</h3><div class="roles-screen__permission-list"><VfCheckbox v-for="permission in permissionGroup.items" :key="permission.code" v-model="permission.selected" :label="permission.name" :disabled="saving || permission.locked" /></div></section></div></div></template></VfTabs></VfCard></form>
  </div>
</template>

<style scoped>
.roles-screen { display: grid; gap: var(--vf-section-gap); }
.roles-screen__column-select-all { display: flex; padding: 0.25rem 0 0.5rem; border-block-end: 1px solid var(--vf-color-border); }
.roles-screen__role-link { color: var(--vf-color-text-link); text-decoration: none; }
.roles-screen__role-link:hover { text-decoration: underline; }
.roles-screen__fields { display: grid; gap: var(--vf-section-gap); width: 100%; }
.roles-screen__fields :deep(.vf-field) { width: 100%; }
.roles-screen__permissions { display: grid; gap: var(--vf-section-gap); width: 100%; }
.roles-screen__permission-grid { display: grid; gap: var(--vf-section-gap); }
.roles-screen__permission-group { display: grid; align-content: start; gap: 1rem; padding: 1rem; border: 1px solid var(--vf-color-border); border-radius: var(--vf-radius-surface); }
.roles-screen__permission-group h3 { margin: 0; font-size: 1rem; }
.roles-screen__permission-list { display: grid; grid-template-columns: minmax(0, 1fr); gap: 0.75rem var(--vf-section-gap); }
.roles-screen__permission-list :deep(.vf-checkbox) { min-width: 0; }
.roles-screen__permissions-error { margin: 0; color: var(--vf-color-danger); }
.roles-screen__status { color: var(--vf-color-muted); }
.roles-screen__status--active { color: var(--vf-color-success); }
@media (min-width: 900px) { .roles-screen__permission-grid { display: block; columns: 2; column-gap: var(--vf-section-gap); } .roles-screen__permission-group { width: 100%; margin-block-end: var(--vf-section-gap); break-inside: avoid; } }
@media (min-width: 1200px) { .roles-screen__fields :deep(.vf-field) { grid-template-columns: minmax(14rem, 25%) minmax(0, 1fr); column-gap: var(--vf-section-gap); align-items: start; } .roles-screen__fields :deep(.vf-field__label) { align-self: start; justify-self: end; padding-block-start: 0.65rem; overflow-wrap: anywhere; text-align: end; } .roles-screen__fields :deep(.vf-field__control), .roles-screen__fields :deep(.vf-field__description), .roles-screen__fields :deep(.vf-field__error) { grid-column: 2; } .roles-screen__fields :deep(.vf-field__control) { grid-row: 1; } .roles-screen__active-field :deep(.vf-field__label) { align-self: center; padding-block-start: 0; } .roles-screen__fields > :deep(.vf-field) { grid-column: 1 / -1; } }
</style>
