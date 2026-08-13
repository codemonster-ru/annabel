<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { CmAlert } from '@codemonster-ru/ui-vue';
import { CmButton } from '@codemonster-ru/ui-vue';
import { CmCard } from '@codemonster-ru/ui-vue';
import { CmCheckbox } from '@codemonster-ru/ui-vue';
import AppConfirmDialog from '../../../../Admin/resources/js/components/AppConfirmDialog.vue';
import AppDataTable from '../../../../../resources/js/components/AppDataTable.vue';
import AppDataTableColumnChooser from '../../../../Admin/resources/js/components/AppDataTableColumnChooser.vue';
import AppInput from '../../../../../resources/js/components/AppInput.vue';
import AppDropdown from '../../../../Admin/resources/js/components/AppDropdown.vue';
import CmField from '../../../../../resources/js/components/AppField.vue';
import AppFormLayout from '../../../../Admin/resources/js/components/AppFormLayout.vue';
import AppIconButton from '../../../../Admin/resources/js/components/AppIconButton.vue';
import { CmInput } from '@codemonster-ru/ui-vue';
import AppMenuItem from '../../../../Admin/resources/js/components/AppMenuItem.vue';
import AppMenu from '../../../../Admin/resources/js/components/AppMenu.vue';
import { CmTextarea } from '@codemonster-ru/ui-vue';
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
  { key: 'title', header: 'Title', verticalAlign: 'middle' },
  { key: 'slug', header: 'Slug', verticalAlign: 'middle' },
  { key: 'is_active', header: 'Published', verticalAlign: 'middle' },
  { key: 'sort_order', header: 'Sort order', verticalAlign: 'middle' },
  { key: 'created_at', header: 'Created', verticalAlign: 'middle' },
  { key: 'updated_at', header: 'Updated', verticalAlign: 'middle' },
];
const formTabs = [
  { value: 'general', label: 'General' },
  { value: 'seo', label: 'SEO' },
];

const emptyPage = () => ({
  id: null,
  created_by: null,
  owner_id: null,
  updated_by: null,
  slug: '',
  title: '',
  meta_title: '',
  meta_description: '',
  content: '',
  is_active: false,
  sort_order: 1,
  active_from: '',
  active_until: '',
});

const pages = ref([]);
const ownerOptions = ref([]);
const page = ref(emptyPage());
const currentPath = typeof window !== 'undefined' ? window.location.pathname : '/admin/pages';
const formMode = computed(() => currentPath === '/admin/pages/create' || /^\/admin\/pages\/\d+\/edit$/.test(currentPath));
const editId = computed(() => currentPath.match(/^\/admin\/pages\/(\d+)\/edit$/)?.[1] || null);
const csrfToken = ref('');
const visibleColumns = ref(columns.map((column) => column.key));
const preferencesSaving = ref(false);
const tablePage = ref(1);
const pageSize = ref(10);
const totalRows = ref(0);
const loading = ref(true);
const saving = ref(false);
const deleting = ref(false);
const deleteCandidate = ref(null);
const error = ref('');
const success = ref('');
const errors = ref({});
const activeTab = ref('general');
const editing = computed(() => editId.value !== null);
const can = (permission) => props.user?.roles?.includes('admin') || props.user?.permissions?.includes(permission);
const ownsPage = (value) => value?.owner_id != null && String(value.owner_id) === String(props.user?.id);
const canUpdatePage = (value) => can('pages.update') || (can('pages.update.own') && ownsPage(value));
const canDeletePage = (value) => can('pages.delete') || (can('pages.delete.own') && ownsPage(value));
const canPublishPage = (value) => can('pages.publish') || (can('pages.publish.own') && (value?.id ? ownsPage(value) : true));
const canAssignOwner = computed(() => editing.value && can('pages.assign_owner') && ownerOptions.value.length > 0);

function firstError(field) {
  const messages = errors.value[field];

  return Array.isArray(messages) && messages.length > 0 ? messages[0] : '';
}

function pageFromPayload(value) {
  return {
    ...emptyPage(),
    ...value,
    active_from: toLocalDateTimeValue(value?.active_from),
    active_until: toLocalDateTimeValue(value?.active_until),
  };
}

async function loadPages() {
  loading.value = true;
  error.value = '';
  const query = new URLSearchParams({
    page: String(tablePage.value),
    per_page: String(pageSize.value),
  });

  try {
    const response = await fetch(`/admin/pages/data?${query}`, {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
    });
    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
      throw new Error(payload.message || 'Unable to load pages.');
    }

    pages.value = Array.isArray(payload.data) ? payload.data : [];
    totalRows.value = Number.isFinite(payload.total) ? payload.total : 0;
    csrfToken.value = payload.csrf_token || '';
    if (Array.isArray(payload.visible_columns) && payload.visible_columns.includes('actions')) {
      visibleColumns.value = payload.visible_columns;
    }
  } catch (exception) {
    error.value = exception instanceof Error ? exception.message : 'Unable to load pages.';
  } finally {
    loading.value = false;
  }
}

async function saveColumnPreferences(next) {
  const previous = visibleColumns.value;
  visibleColumns.value = columns
    .map((column) => column.key)
    .filter((key) => next.includes(key));
  preferencesSaving.value = true;

  const body = new FormData();
  body.append('_token', csrfToken.value);
  body.append('visible_columns', JSON.stringify(visibleColumns.value));

  try {
    const response = await fetch('/admin/pages/preferences', {
      method: 'POST',
      headers: { Accept: 'application/json' },
      body,
      credentials: 'same-origin',
    });
    const payload = await response.json().catch(() => ({}));

    if (!response.ok) throw new Error(payload.message || 'Unable to save column preferences.');
  } catch (exception) {
    visibleColumns.value = previous;
    error.value = exception instanceof Error ? exception.message : 'Unable to save column preferences.';
  } finally {
    preferencesSaving.value = false;
  }
}

async function loadPage() {
  loading.value = true;
  error.value = '';

  try {
    const response = await fetch(`/admin/pages/data/${editId.value}`, {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
    });
    const payload = await response.json().catch(() => ({}));

    if (!response.ok) throw new Error(payload.message || 'Unable to load page.');

    page.value = pageFromPayload(payload.page);
    ownerOptions.value = Array.isArray(payload.owner_options) ? payload.owner_options : [];
    csrfToken.value = payload.csrf_token || '';
  } catch (exception) {
    error.value = exception instanceof Error ? exception.message : 'Unable to load page.';
  } finally {
    loading.value = false;
  }
}

function editPage(value) {
  if (!canUpdatePage(value)) return;
  window.location.assign(`/admin/pages/${value.id}/edit`);
}

function publicPageUrl(value) {
  return value.slug === 'home' ? '/' : `/pages/${value.slug}`;
}

function newPage() {
  window.location.assign('/admin/pages/create');
}

function backToPages() {
  window.location.assign('/admin/pages');
}

async function savePage() {
  if (saving.value) return;

  saving.value = true;
  error.value = '';
  success.value = '';
  errors.value = {};

  const body = new FormData();
  body.append('_token', csrfToken.value);
  body.append('slug', page.value.slug);
  body.append('title', page.value.title);
  body.append('meta_title', page.value.meta_title);
  body.append('meta_description', page.value.meta_description);
  body.append('content', page.value.content);
  body.append('is_active', page.value.is_active ? '1' : '0');
  body.append('sort_order', String(page.value.sort_order ?? 1));
  body.append('active_from', localDateTimeValueToIso(page.value.active_from) ?? page.value.active_from);
  body.append('active_until', localDateTimeValueToIso(page.value.active_until) ?? page.value.active_until);
  if (canAssignOwner.value) body.append('owner_id', String(page.value.owner_id ?? ''));

  const endpoint = editing.value ? `/admin/pages/data/${page.value.id}` : '/admin/pages/data';

  try {
    const response = await fetch(endpoint, {
      method: 'POST',
      headers: { Accept: 'application/json' },
      body,
      credentials: 'same-origin',
    });
    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
      if (response.status === 422) errors.value = payload.errors || {};
      throw new Error(payload.message || 'Unable to save page.');
    }

    const saved = pageFromPayload(payload.page);
    const index = pages.value.findIndex((item) => item.id === saved.id);

    if (index === -1) pages.value.unshift(saved);
    else pages.value[index] = saved;

    page.value = saved;
    success.value = payload.message || 'Page saved successfully.';

    if (!editing.value) {
      window.location.assign('/admin/pages');
    }
  } catch (exception) {
    error.value = exception instanceof Error ? exception.message : 'Unable to save page.';
  } finally {
    saving.value = false;
  }
}

async function deletePage(value = page.value) {
  if (!value?.id || !canDeletePage(value) || deleting.value) return;

  deleting.value = true;
  error.value = '';

  const body = new FormData();
  body.append('_token', csrfToken.value);

  try {
    const response = await fetch(`/admin/pages/data/${value.id}/delete`, {
      method: 'POST',
      headers: { Accept: 'application/json' },
      body,
      credentials: 'same-origin',
    });
    const payload = await response.json().catch(() => ({}));

    if (!response.ok) throw new Error(payload.message || 'Unable to delete page.');

    window.location.assign('/admin/pages');
  } catch (exception) {
    error.value = exception instanceof Error ? exception.message : 'Unable to delete page.';
  } finally {
    deleting.value = false;
  }
}

watch([tablePage, pageSize], loadPages);
onMounted(() => (formMode.value ? (editId.value ? loadPage() : loadPages()) : loadPages()));
</script>

<template>
  <div class="pages-screen">
    <AppConfirmDialog
      :open="Boolean(deleteCandidate)"
      title="Delete page?"
      :description="deleteCandidate ? `The page “${deleteCandidate.title}” will be deleted.` : ''"
      confirm-label="Delete"
      confirm-variant="danger"
      :loading="deleting"
      :disabled="deleting"
      :close-on-confirm="false"
      @update:open="deleteCandidate = $event ? deleteCandidate : null"
      @confirm="deletePage(deleteCandidate)"
    />
    <Teleport v-if="formMode" to="#admin-page-actions">
      <CmButton type="submit" form="pages-page-form" :loading="saving" :disabled="loading || deleting">
        {{ saving ? 'Saving...' : 'Save page' }}
      </CmButton>
      <CmButton variant="secondary" :disabled="saving || deleting" @click="backToPages">
        Back
      </CmButton>
    </Teleport>
    <Teleport v-else-if="can('pages.create')" to="#admin-page-actions">
      <CmButton variant="primary" :disabled="loading || saving" @click="newPage">New page</CmButton>
    </Teleport>

    <CmAlert v-if="error" tone="danger" title="Pages">
      {{ error }}
    </CmAlert>
    <CmAlert v-if="success" tone="success" title="Pages">
      {{ success }}
    </CmAlert>

    <div v-if="!formMode" class="pages-screen__list">
      <AppDataTable
        :columns="columns"
        :visible-column-keys="visibleColumns"
        :rows="pages"
        row-key="id"
        striped
        column-dividers
        :loading="loading"
        pagination
        :page="tablePage"
        :page-size="pageSize"
        :total-rows="totalRows"
        empty-text="No pages found"
        @update:page="tablePage = $event"
        @update:page-size="pageSize = $event"
      >
        <template #header-actions>
          <AppDataTableColumnChooser
            :columns="columns"
            :model-value="visibleColumns"
            :required-column-keys="['actions']"
            :disabled="preferencesSaving"
            @update:model-value="saveColumnPreferences"
          />
        </template>
        <template #cell-actions="{ row }">
          <AppDropdown v-if="canUpdatePage(row) || canDeletePage(row) || row.is_active" placement="bottom-start">
            <template #trigger>
              <AppIconButton
                :icon="icons.bars"
                variant="ghost"
                size="sm"
                :aria-label="`Actions for ${row.title}`"
                :title="`Actions for ${row.title}`"
              />
            </template>
            <AppMenu>
              <AppMenuItem v-if="canUpdatePage(row)" label="Edit" :icon="icons.pencil" @select="editPage(row)" />
              <AppMenuItem
                v-if="row.is_active"
                label="View page"
                :icon="icons.externalLink"
                :href="publicPageUrl(row)"
                target="_blank"
                rel="noopener noreferrer"
              />
              <AppMenuItem v-if="canDeletePage(row)" label="Delete" :icon="icons.trash" tone="danger" @select="deleteCandidate = row" />
            </AppMenu>
          </AppDropdown>
        </template>
        <template #cell-title="{ row }">
          <a v-if="canUpdatePage(row)" class="pages-screen__title-link" :href="`/admin/pages/${row.id}/edit`">
            {{ row.title }}
          </a>
          <span v-else>{{ row.title }}</span>
        </template>
        <template #cell-slug="{ value }">
          /pages/{{ value }}
        </template>
        <template #cell-is_active="{ value }">
          <span :class="['pages-screen__status', { 'pages-screen__status--active': value }]">
            {{ value ? 'Yes' : 'No' }}
          </span>
        </template>
        <template #cell-sort_order="{ value }">{{ value }}</template>
      <template #cell-updated_at="{ value }">
        {{ formatDateTime(value) }}
      </template>
      <template #cell-created_at="{ value }">
        {{ formatDateTime(value) }}
      </template>
    </AppDataTable>
    </div>

    <form id="pages-page-form" v-else class="pages-screen__form" novalidate @submit.prevent="savePage">
      <CmCard>
          <CmTabs v-model="activeTab" :items="formTabs">
            <template #panel="{ activeValue }">
              <AppFormLayout v-if="activeValue === 'general'" mode="responsive" label-width="minmax(14rem, 25%)">
                <CmField class="pages-screen__active-field" label="Published">
                  <template #default="{ controlId }">
                    <CmCheckbox :id="controlId" v-model="page.is_active" :disabled="saving || !canPublishPage(page)" />
                  </template>
                </CmField>

                <CmField class="pages-screen__activity-field" label="Publish from" :error="firstError('active_from')">
                  <template #default="{ controlId, describedBy, invalid }">
                    <AppInput
                      :id="controlId"
                      v-model="page.active_from"
                      type="datetime-local"
                      :aria-describedby="describedBy"
                      :invalid="invalid"
                      :disabled="saving || !canPublishPage(page)"
                    />
                  </template>
                </CmField>

                <CmField class="pages-screen__activity-field" label="Publish until" :error="firstError('active_until')">
                  <template #default="{ controlId, describedBy, invalid }">
                    <AppInput
                      :id="controlId"
                      v-model="page.active_until"
                      type="datetime-local"
                      :aria-describedby="describedBy"
                      :invalid="invalid"
                      :disabled="saving || !canPublishPage(page)"
                    />
                  </template>
                </CmField>

                <CmField v-if="canAssignOwner" label="Owner" :error="firstError('owner_id')">
                  <template #default="{ controlId, describedBy, invalid }">
                    <select
                      :id="controlId"
                      v-model="page.owner_id"
                      class="pages-screen__owner-select"
                      :aria-describedby="describedBy"
                      :aria-invalid="invalid"
                      :disabled="saving"
                    >
                      <option v-for="option in ownerOptions" :key="option.id" :value="option.id">
                        {{ option.label }}
                      </option>
                    </select>
                  </template>
                </CmField>

                <CmField label="Title" :error="firstError('title')" required>
                  <template #default="{ controlId, describedBy, invalid }">
                    <CmInput :id="controlId" v-model="page.title" :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" required />
                  </template>
                </CmField>

                <CmField label="Slug" :error="firstError('slug')" required>
                  <template #default="{ controlId, describedBy, invalid }">
                    <CmInput :id="controlId" v-model="page.slug" :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" required />
                  </template>
                </CmField>

                <CmField label="Sort order" :error="firstError('sort_order')">
                  <template #default="{ controlId, describedBy, invalid }">
                    <AppInput :id="controlId" v-model="page.sort_order" type="number" min="1" max="1000000" :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" />
                  </template>
                </CmField>

                <CmField label="Content" :error="firstError('content')" required>
                  <template #default="{ controlId, describedBy, invalid }">
                    <CmTextarea
                      :id="controlId"
                      v-model="page.content"
                      :aria-describedby="describedBy"
                      :invalid="invalid"
                      :disabled="saving"
                      rows="10"
                      required
                    />
                  </template>
                </CmField>

              </AppFormLayout>

              <AppFormLayout v-else-if="activeValue === 'seo'" mode="responsive" label-width="minmax(14rem, 25%)">
                <CmField label="Meta title" :error="firstError('meta_title')">
                  <template #default="{ controlId, describedBy, invalid }">
                    <CmInput
                      :id="controlId"
                      v-model="page.meta_title"
                      :aria-describedby="describedBy"
                      :invalid="invalid"
                      :disabled="saving"
                    />
                  </template>
                </CmField>

                <CmField label="Meta description" :error="firstError('meta_description')">
                  <template #default="{ controlId, describedBy, invalid }">
                    <CmTextarea
                      :id="controlId"
                      v-model="page.meta_description"
                      :aria-describedby="describedBy"
                      :invalid="invalid"
                      :disabled="saving"
                      rows="5"
                    />
                  </template>
                </CmField>
              </AppFormLayout>
            </template>
          </CmTabs>
      </CmCard>
    </form>
  </div>
</template>

<style scoped>
.pages-screen {
  display: grid;
  gap: var(--cm-space-6);
}

.pages-screen__owner-select {
  width: 100%;
  min-height: var(--cm-control-height-md);
  padding-inline: var(--cm-space-3);
  color: var(--cm-color-text-primary);
  background: var(--cm-color-background-surface);
  border: var(--cm-border-width) solid var(--cm-color-border-default);
  border-radius: var(--cm-radius-control-tight);
}

@media (min-width: 768px) {
  .pages-screen__active-field :deep(.cm-field__label) {
    align-self: center;
    justify-self: end;
    padding-block-start: 0;
  }
}
.pages-screen__status {
  color: var(--cm-color-text-muted);
}

.pages-screen__status--active {
  color: var(--cm-color-status-success-subtle-foreground);
}

.pages-screen__empty {
  color: var(--cm-color-text-muted);
}

.pages-screen__title-link {
  color: var(--cm-color-text-link);
  text-decoration: none;
}

.pages-screen__title-link:hover {
  text-decoration: underline;
}

</style>
