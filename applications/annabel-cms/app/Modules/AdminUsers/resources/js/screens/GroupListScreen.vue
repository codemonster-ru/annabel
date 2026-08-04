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
const groupFormTabs = [{ value: 'general', label: 'General' }];
const emptyGroup = () => ({ id: null, name: '', code: '', description: '', is_active: true, active_from: '', active_until: '', sort_order: 1 });
const currentPath = typeof window !== 'undefined' ? window.location.pathname : '/admin/groups';
const formMode = computed(() => currentPath === '/admin/groups/create' || /^\/admin\/groups\/\d+\/edit$/.test(currentPath));
const editId = computed(() => currentPath.match(/^\/admin\/groups\/(\d+)\/edit$/)?.[1] || null);
const editing = computed(() => editId.value !== null);
const rows = ref([]); const group = ref(emptyGroup()); const visibleColumns = ref(columns.map((column) => column.key));
const page = ref(1); const pageSize = ref(10); const totalRows = ref(0); const csrfToken = ref('');
const loading = ref(true); const saving = ref(false); const deleting = ref(false); const preferencesSaving = ref(false);
const error = ref(''); const success = ref(''); const errors = ref({});
const activeTab = ref('general');
const activeFromMax = computed(() => shiftLocalDateTime(group.value.active_until, -1));
const activeUntilMin = computed(() => shiftLocalDateTime(group.value.active_from, 1));

function firstError(field) { const messages = errors.value[field]; return Array.isArray(messages) && messages.length ? messages[0] : ''; }
function shiftLocalDateTime(value, minutes) {
  if (!value) return undefined;

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return undefined;

  date.setMinutes(date.getMinutes() + minutes);

  return toLocalDateTimeValue(date);
}
function groupFromPayload(value) {
  return {
    ...emptyGroup(),
    ...value,
    active_from: toLocalDateTimeValue(value?.active_from),
    active_until: toLocalDateTimeValue(value?.active_until),
  };
}
async function loadGroups() {
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
async function loadGroup() {
  loading.value = true; error.value = '';
  try {
    const response = await fetch(`/admin/groups/data/${editId.value}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
    const payload = await response.json().catch(() => ({})); if (!response.ok) throw new Error(payload.message || 'Unable to load group.');
    group.value = groupFromPayload(payload.group || {}); csrfToken.value = payload.csrf_token || '';
  } catch (exception) { error.value = exception instanceof Error ? exception.message : 'Unable to load group.'; } finally { loading.value = false; }
}
async function saveColumnPreferences(next) {
  const previous = visibleColumns.value; visibleColumns.value = columns.map((column) => column.key).filter((key) => next.includes(key)); preferencesSaving.value = true;
  const body = new FormData(); body.append('_token', csrfToken.value); body.append('visible_columns', JSON.stringify(visibleColumns.value));
  try { const response = await fetch('/admin/groups/preferences', { method: 'POST', headers: { Accept: 'application/json' }, body, credentials: 'same-origin' }); if (!response.ok) throw new Error('Unable to save column preferences.'); }
  catch (exception) { visibleColumns.value = previous; error.value = exception instanceof Error ? exception.message : 'Unable to save column preferences.'; } finally { preferencesSaving.value = false; }
}
function toggleColumn(key, value) { if (key !== 'actions') saveColumnPreferences(value ? [...visibleColumns.value, key] : visibleColumns.value.filter((column) => column !== key)); }
function toggleAllColumns(value) { saveColumnPreferences(value ? columns.map((column) => column.key) : ['actions']); }
function editGroup(value) { window.location.assign(`/admin/groups/${value.id}/edit`); }
function newGroup() { window.location.assign('/admin/groups/create'); }
function backToGroups() { window.location.assign('/admin/groups'); }
async function saveGroup() {
  if (saving.value) return; saving.value = true; error.value = ''; success.value = ''; errors.value = {};
  const body = new FormData(); body.append('_token', csrfToken.value); body.append('name', group.value.name); body.append('code', group.value.code); body.append('description', group.value.description || ''); body.append('is_active', group.value.is_active ? '1' : '0'); body.append('active_from', localDateTimeValueToIso(group.value.active_from) ?? group.value.active_from); body.append('active_until', localDateTimeValueToIso(group.value.active_until) ?? group.value.active_until); body.append('sort_order', String(group.value.sort_order ?? 1));
  const endpoint = editing.value ? `/admin/groups/data/${group.value.id}` : '/admin/groups/data';
  try {
    const response = await fetch(endpoint, { method: 'POST', headers: { Accept: 'application/json' }, body, credentials: 'same-origin' }); const payload = await response.json().catch(() => ({}));
    if (!response.ok) { if (response.status === 422) errors.value = payload.errors || {}; throw new Error(payload.message || 'Unable to save group.'); }
    group.value = groupFromPayload(payload.group || {}); success.value = payload.message || 'Group saved successfully.'; if (!editing.value) window.location.assign('/admin/groups');
  } catch (exception) { error.value = exception instanceof Error ? exception.message : 'Unable to save group.'; } finally { saving.value = false; }
}
async function deleteGroup(value) {
  if (!value?.id || deleting.value || !window.confirm('Delete this group?')) return; deleting.value = true;
  const body = new FormData(); body.append('_token', csrfToken.value);
  try { const response = await fetch(`/admin/groups/data/${value.id}/delete`, { method: 'POST', headers: { Accept: 'application/json' }, body, credentials: 'same-origin' }); const payload = await response.json().catch(() => ({})); if (!response.ok) throw new Error(payload.message || 'Unable to delete group.'); window.location.assign('/admin/groups'); }
  catch (exception) { error.value = exception instanceof Error ? exception.message : 'Unable to delete group.'; } finally { deleting.value = false; }
}
watch([page, pageSize], loadGroups); onMounted(() => (formMode.value ? (editId.value ? loadGroup() : (loading.value = false)) : loadGroups()));
</script>

<template>
  <div class="groups-screen">
    <Teleport v-if="formMode" to="#admin-page-actions"><VfButton type="submit" form="groups-group-form" :loading="saving" :disabled="loading || deleting">{{ saving ? 'Saving...' : 'Save group' }}</VfButton><VfButton variant="secondary" :disabled="saving || deleting" @click="backToGroups">Back</VfButton></Teleport>
    <Teleport v-else to="#admin-page-actions"><VfButton variant="primary" :disabled="loading || saving" @click="newGroup">New group</VfButton></Teleport>
    <VfAlert v-if="error" tone="danger" title="Groups">{{ error }}</VfAlert><VfAlert v-if="success" tone="success" title="Groups">{{ success }}</VfAlert>
    <div v-if="!formMode" class="groups-screen__list">
      <VfDataTable :columns="columns" :visible-column-keys="visibleColumns" :rows="rows" row-key="id" striped column-dividers :loading="loading" pagination pagination-mode="manual" :page="page" :page-size="pageSize" :total-rows="totalRows" empty-text="No groups found" @update:page="page = $event" @update:page-size="pageSize = $event">
        <template #header-actions><VfDropdown placement="bottom-start" :close-on-select="false"><template #trigger><VfIconButton :icon="icons.gear" variant="ghost" size="sm" aria-label="Configure columns" title="Configure columns" :disabled="preferencesSaving" /></template><div class="groups-screen__column-select-all"><VfCheckbox label="All columns" :model-value="visibleColumns.length === columns.length" :disabled="preferencesSaving" @update:model-value="toggleAllColumns" /></div><VfCheckbox v-for="column in columns" :key="column.key" :model-value="visibleColumns.includes(column.key)" :label="columnLabels[column.key]" :disabled="column.key === 'actions' || preferencesSaving" @update:model-value="toggleColumn(column.key, $event)" /></VfDropdown></template>
        <template #cell-actions="{ row }"><VfDropdown placement="bottom-start"><template #trigger><VfIconButton :icon="icons.bars" variant="ghost" size="sm" aria-label="Actions" title="Actions" :disabled="deleting" /></template><VfMenu><VfMenuItem label="Edit" :icon="icons.pencil" @select="editGroup(row)" /><VfMenuItem label="Delete" :icon="icons.trash" tone="danger" @select="deleteGroup(row)" /></VfMenu></VfDropdown></template>
        <template #cell-name="{ value, row }"><a class="groups-screen__group-link" :href="`/admin/groups/${row.id}/edit`"><strong>{{ value }}</strong></a></template>
        <template #cell-code="{ value }">{{ value }}</template>
        <template #cell-is_active="{ value }"><span :class="['groups-screen__status', { 'groups-screen__status--active': value }]">{{ value ? 'Yes' : 'No' }}</span></template>
        <template #cell-sort_order="{ value }">{{ value }}</template>
        <template #cell-created_at="{ value }">{{ formatDateTime(value) }}</template><template #cell-updated_at="{ value }">{{ formatDateTime(value) }}</template>
      </VfDataTable>
    </div>
    <form id="groups-group-form" v-else class="groups-screen__form" novalidate @submit.prevent="saveGroup"><VfCard><VfTabs v-model="activeTab" :items="groupFormTabs"><template #panel="{ activeValue }"><div v-if="activeValue === 'general'" class="groups-screen__fields"><VfField class="groups-screen__active-field" label="Active"><template #default="{ controlId }"><VfCheckbox :id="controlId" v-model="group.is_active" :disabled="saving" /></template></VfField><VfField class="groups-screen__activity-field" label="Active from" :error="firstError('active_from')"><template #default="{ controlId, describedBy, invalid }"><VfDatePicker :id="controlId" v-model="group.active_from" :max="activeFromMax" show-time clearable :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" /></template></VfField><VfField class="groups-screen__activity-field" label="Active until" :error="firstError('active_until')"><template #default="{ controlId, describedBy, invalid }"><VfDatePicker :id="controlId" v-model="group.active_until" :min="activeUntilMin" show-time clearable :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" /></template></VfField><VfField label="Name" :error="firstError('name')" required><template #default="{ controlId, describedBy, invalid }"><VfInput :id="controlId" v-model="group.name" :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" required /></template></VfField><VfField label="Code" :error="firstError('code')" required><template #default="{ controlId, describedBy, invalid }"><VfInput :id="controlId" v-model="group.code" :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" required /></template></VfField><VfField label="Description" :error="firstError('description')"><template #default="{ controlId, describedBy, invalid }"><VfTextarea :id="controlId" v-model="group.description" :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" maxlength="255" /></template></VfField><VfField label="Sort order" :error="firstError('sort_order')"><template #default="{ controlId, describedBy, invalid }"><VfInput :id="controlId" v-model="group.sort_order" type="number" min="1" max="1000000" :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" /></template></VfField></div></template></VfTabs></VfCard></form>
  </div>
</template>

<style scoped>
.groups-screen { display: grid; gap: var(--vf-section-gap); }
.groups-screen__column-select-all { display: flex; padding: 0.25rem 0 0.5rem; border-block-end: 1px solid var(--vf-color-border); }
.groups-screen__group-link { color: var(--vf-color-text-link); text-decoration: none; }
.groups-screen__group-link:hover { text-decoration: underline; }
.groups-screen__fields { display: grid; gap: var(--vf-section-gap); width: 100%; }
.groups-screen__fields :deep(.vf-field) { width: 100%; }
.groups-screen__status { color: var(--vf-color-muted); }
.groups-screen__status--active { color: var(--vf-color-success); }
@media (min-width: 1200px) { .groups-screen__fields :deep(.vf-field) { grid-template-columns: minmax(14rem, 25%) minmax(0, 1fr); column-gap: var(--vf-section-gap); align-items: start; } .groups-screen__fields :deep(.vf-field__label) { align-self: start; justify-self: end; padding-block-start: 0.65rem; overflow-wrap: anywhere; text-align: end; } .groups-screen__fields :deep(.vf-field__control), .groups-screen__fields :deep(.vf-field__description), .groups-screen__fields :deep(.vf-field__error) { grid-column: 2; } .groups-screen__fields :deep(.vf-field__control) { grid-row: 1; } .groups-screen__active-field :deep(.vf-field__label) { align-self: center; padding-block-start: 0; } .groups-screen__fields > :deep(.vf-field) { grid-column: 1 / -1; } }
</style>
