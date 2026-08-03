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
import { VfTextarea } from '@codemonster-ru/vueforge-core/textarea';
import { icons } from '@codemonster-ru/vueforge-icons';

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
const dateFormatter = new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' });
const groupFormTabs = [{ value: 'general', label: 'General' }];
const emptyGroup = () => ({ id: null, name: '', code: '', description: '', is_active: true, sort_order: 1 });
const currentPath = typeof window !== 'undefined' ? window.location.pathname : '/admin/groups';
const formMode = computed(() => currentPath === '/admin/groups/create' || /^\/admin\/groups\/\d+\/edit$/.test(currentPath));
const editId = computed(() => currentPath.match(/^\/admin\/groups\/(\d+)\/edit$/)?.[1] || null);
const editing = computed(() => editId.value !== null);
const rows = ref([]); const role = ref(emptyGroup()); const visibleColumns = ref(columns.map((column) => column.key));
const page = ref(1); const pageSize = ref(10); const totalRows = ref(0); const csrfToken = ref('');
const loading = ref(true); const saving = ref(false); const deleting = ref(false); const preferencesSaving = ref(false);
const error = ref(''); const success = ref(''); const errors = ref({});
const activeTab = ref('general');

function firstError(field) { const messages = errors.value[field]; return Array.isArray(messages) && messages.length ? messages[0] : ''; }
function formatDate(value) { if (value == null || value === '') return '—'; const date = new Date(value); return Number.isNaN(date.getTime()) ? '—' : dateFormatter.format(date); }

async function loadRoles() {
  loading.value = true; error.value = '';
  try {
    const query = new URLSearchParams({ page: String(page.value), per_page: String(pageSize.value) });
    const response = await fetch(`/admin/groups/data?${query}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(payload.message || 'Unable to load groups.');
    rows.value = Array.isArray(payload.data) ? payload.data : []; totalRows.value = Number(payload.total) || 0; csrfToken.value = payload.csrf_token || '';
    if (Array.isArray(payload.visible_columns) && payload.visible_columns.includes('actions')) visibleColumns.value = payload.visible_columns;
  } catch (exception) { error.value = exception instanceof Error ? exception.message : 'Unable to load groups.'; } finally { loading.value = false; }
}
async function loadRole() {
  loading.value = true; error.value = '';
  try {
    const response = await fetch(`/admin/groups/data/${editId.value}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
    const payload = await response.json().catch(() => ({})); if (!response.ok) throw new Error(payload.message || 'Unable to load role.');
    role.value = { ...emptyGroup(), ...(payload.group || {}) }; csrfToken.value = payload.csrf_token || '';
  } catch (exception) { error.value = exception instanceof Error ? exception.message : 'Unable to load role.'; } finally { loading.value = false; }
}
async function saveColumnPreferences(next) {
  const previous = visibleColumns.value; visibleColumns.value = columns.map((column) => column.key).filter((key) => next.includes(key)); preferencesSaving.value = true;
  const body = new FormData(); body.append('_token', csrfToken.value); body.append('visible_columns', JSON.stringify(visibleColumns.value));
  try { const response = await fetch('/admin/groups/preferences', { method: 'POST', headers: { Accept: 'application/json' }, body, credentials: 'same-origin' }); if (!response.ok) throw new Error('Unable to save column preferences.'); }
  catch (exception) { visibleColumns.value = previous; error.value = exception instanceof Error ? exception.message : 'Unable to save column preferences.'; } finally { preferencesSaving.value = false; }
}
function toggleColumn(key, value) { if (key !== 'actions') saveColumnPreferences(value ? [...visibleColumns.value, key] : visibleColumns.value.filter((column) => column !== key)); }
function toggleAllColumns(value) { saveColumnPreferences(value ? columns.map((column) => column.key) : ['actions']); }
function editRole(value) { window.location.assign(`/admin/groups/${value.id}/edit`); }
function newRole() { window.location.assign('/admin/groups/create'); }
function backToRoles() { window.location.assign('/admin/groups'); }
async function saveRole() {
  if (saving.value) return; saving.value = true; error.value = ''; success.value = ''; errors.value = {};
  const body = new FormData(); body.append('_token', csrfToken.value); body.append('name', role.value.name); body.append('code', role.value.code); body.append('description', role.value.description || ''); body.append('is_active', role.value.is_active ? '1' : '0'); body.append('sort_order', String(role.value.sort_order ?? 1));
  const endpoint = editing.value ? `/admin/groups/data/${role.value.id}` : '/admin/groups/data';
  try {
    const response = await fetch(endpoint, { method: 'POST', headers: { Accept: 'application/json' }, body, credentials: 'same-origin' }); const payload = await response.json().catch(() => ({}));
    if (!response.ok) { if (response.status === 422) errors.value = payload.errors || {}; throw new Error(payload.message || 'Unable to save role.'); }
    role.value = { ...emptyGroup(), ...(payload.group || {}) }; success.value = payload.message || 'Group saved successfully.'; if (!editing.value) window.location.assign('/admin/groups');
  } catch (exception) { error.value = exception instanceof Error ? exception.message : 'Unable to save role.'; } finally { saving.value = false; }
}
async function deleteRole(value) {
  if (!value?.id || deleting.value || !window.confirm('Delete this role?')) return; deleting.value = true;
  const body = new FormData(); body.append('_token', csrfToken.value);
  try { const response = await fetch(`/admin/groups/data/${value.id}/delete`, { method: 'POST', headers: { Accept: 'application/json' }, body, credentials: 'same-origin' }); const payload = await response.json().catch(() => ({})); if (!response.ok) throw new Error(payload.message || 'Unable to delete group.'); window.location.assign('/admin/groups'); }
  catch (exception) { error.value = exception instanceof Error ? exception.message : 'Unable to delete role.'; } finally { deleting.value = false; }
}
watch([page, pageSize], loadRoles); onMounted(() => (formMode.value ? (editId.value ? loadRole() : (loading.value = false)) : loadRoles()));
</script>

<template>
  <div class="groups-screen">
    <Teleport v-if="formMode" to="#admin-page-actions"><VfButton type="submit" form="groups-group-form" :loading="saving" :disabled="loading || deleting">{{ saving ? 'Saving...' : 'Save group' }}</VfButton><VfButton variant="secondary" :disabled="saving || deleting" @click="backToRoles">Back</VfButton></Teleport>
    <Teleport v-else to="#admin-page-actions"><VfButton variant="primary" :disabled="loading || saving" @click="newRole">New group</VfButton></Teleport>
    <VfAlert v-if="error" tone="danger" title="Groups">{{ error }}</VfAlert><VfAlert v-if="success" tone="success" title="Groups">{{ success }}</VfAlert>
    <div v-if="!formMode" class="roles-screen__list">
      <VfDataTable :columns="columns" :visible-column-keys="visibleColumns" :rows="rows" row-key="id" striped column-dividers :loading="loading" pagination pagination-mode="manual" :page="page" :page-size="pageSize" :total-rows="totalRows" empty-text="No groups found" @update:page="page = $event" @update:page-size="pageSize = $event">
        <template #header-actions><VfDropdown placement="bottom-start" :close-on-select="false"><template #trigger><VfIconButton :icon="icons.gear" variant="ghost" size="sm" aria-label="Configure columns" title="Configure columns" :disabled="preferencesSaving" /></template><div class="roles-screen__column-select-all"><VfCheckbox label="All columns" :model-value="visibleColumns.length === columns.length" :disabled="preferencesSaving" @update:model-value="toggleAllColumns" /></div><VfCheckbox v-for="column in columns" :key="column.key" :model-value="visibleColumns.includes(column.key)" :label="columnLabels[column.key]" :disabled="column.key === 'actions' || preferencesSaving" @update:model-value="toggleColumn(column.key, $event)" /></VfDropdown></template>
        <template #cell-actions="{ row }"><VfDropdown placement="bottom-start"><template #trigger><VfIconButton :icon="icons.bars" variant="ghost" size="sm" aria-label="Actions" title="Actions" :disabled="deleting" /></template><VfMenu><VfMenuItem label="Edit" :icon="icons.pencil" @select="editRole(row)" /><VfMenuItem label="Delete" :icon="icons.trash" tone="danger" @select="deleteRole(row)" /></VfMenu></VfDropdown></template>
        <template #cell-name="{ value, row }"><a class="roles-screen__role-link" :href="`/admin/groups/${row.id}/edit`"><strong>{{ value }}</strong></a></template>
        <template #cell-code="{ value }">{{ value }}</template>
        <template #cell-is_active="{ value }"><span :class="['groups-screen__status', { 'groups-screen__status--active': value }]">{{ value ? 'Yes' : 'No' }}</span></template>
        <template #cell-sort_order="{ value }">{{ value }}</template>
        <template #cell-created_at="{ value }">{{ formatDate(value) }}</template><template #cell-updated_at="{ value }">{{ formatDate(value) }}</template>
      </VfDataTable>
    </div>
    <form id="groups-group-form" v-else class="groups-screen__form" novalidate @submit.prevent="saveRole"><VfCard><VfTabs v-model="activeTab" :items="groupFormTabs"><template #panel="{ activeValue }"><div v-if="activeValue === 'general'" class="groups-screen__fields"><VfField class="groups-screen__active-field" label="Active"><template #default="{ controlId }"><VfCheckbox :id="controlId" v-model="role.is_active" :disabled="saving" /></template></VfField><VfField label="Name" description="Letters, numbers, spaces, underscores, or hyphens." :error="firstError('name')" required><template #default="{ controlId, describedBy, invalid }"><VfInput :id="controlId" v-model="role.name" :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" required /></template></VfField><VfField label="Code" description="Stable lowercase identifier for use in code." :error="firstError('code')" required><template #default="{ controlId, describedBy, invalid }"><VfInput :id="controlId" v-model="role.code" :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" required /></template></VfField><VfField label="Description" description="Up to 255 characters." :error="firstError('description')"><template #default="{ controlId, describedBy, invalid }"><VfTextarea :id="controlId" v-model="role.description" :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" maxlength="255" /></template></VfField><VfField label="Sort order" description="Lower numbers appear first." :error="firstError('sort_order')"><template #default="{ controlId, describedBy, invalid }"><VfInput :id="controlId" v-model="role.sort_order" type="number" min="1" max="1000000" :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" /></template></VfField></div></template></VfTabs></VfCard></form>
  </div>
</template>

<style scoped>
.roles-screen { display: grid; gap: var(--vf-section-gap); }
.roles-screen__column-select-all { display: flex; padding: 0.25rem 0 0.5rem; border-block-end: 1px solid var(--vf-color-border); }
.roles-screen__role-link { color: var(--vf-color-text-link); text-decoration: none; }
.roles-screen__role-link:hover { text-decoration: underline; }
.groups-screen__fields { display: grid; gap: var(--vf-section-gap); width: 100%; }
.groups-screen__fields :deep(.vf-field) { width: 100%; }
.groups-screen__status { color: var(--vf-color-muted); }
.groups-screen__status--active { color: var(--vf-color-success); }
@media (min-width: 1200px) { .groups-screen__fields { width: 70%; max-width: 64rem; margin-inline: auto; } .groups-screen__fields :deep(.vf-field) { grid-template-columns: 10rem minmax(0, 1fr); column-gap: var(--vf-section-gap); align-items: start; } .groups-screen__fields :deep(.vf-field__label) { align-self: start; justify-self: end; padding-block-start: 0.65rem; text-align: end; } .groups-screen__fields :deep(.vf-field__control), .groups-screen__fields :deep(.vf-field__description), .groups-screen__fields :deep(.vf-field__error) { grid-column: 2; } .groups-screen__fields :deep(.vf-field__control) { grid-row: 1; } .groups-screen__active-field :deep(.vf-field__label) { align-self: center; padding-block-start: 0; } .groups-screen__fields > :deep(.vf-field) { grid-column: 1 / -1; } }
</style>
