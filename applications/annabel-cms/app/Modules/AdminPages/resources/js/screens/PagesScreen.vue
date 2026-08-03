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
import { VfTextarea } from '@codemonster-ru/vueforge-core/textarea';
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
  { key: 'title', header: 'Title', verticalAlign: 'middle' },
  { key: 'slug', header: 'Slug', verticalAlign: 'middle' },
  { key: 'is_published', header: 'Published', verticalAlign: 'middle' },
  { key: 'sort_order', header: 'Sort order', verticalAlign: 'middle' },
  { key: 'created_at', header: 'Created', verticalAlign: 'middle' },
  { key: 'updated_at', header: 'Updated', verticalAlign: 'middle' },
];
const columnLabels = {
  actions: 'Actions',
  id: 'ID',
  title: 'Title',
  slug: 'Slug',
  is_published: 'Published',
  sort_order: 'Sort order',
  created_at: 'Created',
  updated_at: 'Updated',
};
const formTabs = [
  { value: 'general', label: 'General' },
  { value: 'seo', label: 'SEO' },
];

const emptyPage = () => ({
  id: null,
  slug: '',
  title: '',
  meta_title: '',
  meta_description: '',
  content: '',
  is_published: false,
  sort_order: 1,
  publish_at: '',
  unpublish_at: '',
});

const pages = ref([]);
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
const error = ref('');
const success = ref('');
const errors = ref({});
const activeTab = ref('general');
const editing = computed(() => editId.value !== null);

function firstError(field) {
  const messages = errors.value[field];

  return Array.isArray(messages) && messages.length > 0 ? messages[0] : '';
}

function pageFromPayload(value) {
  return {
    ...emptyPage(),
    ...value,
    publish_at: toLocalDateTimeValue(value?.publish_at),
    unpublish_at: toLocalDateTimeValue(value?.unpublish_at),
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

function toggleColumn(columnKey, value) {
  if (columnKey === 'actions') return;

  const next = value
    ? [...visibleColumns.value, columnKey]
    : visibleColumns.value.filter((key) => key !== columnKey);

  saveColumnPreferences(next);
}

function showAllColumns() {
  saveColumnPreferences(columns.map((column) => column.key));
}

function hideOptionalColumns() {
  saveColumnPreferences(['actions']);
}

function toggleAllColumns(value) {
  if (value) showAllColumns();
  else hideOptionalColumns();
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
    csrfToken.value = payload.csrf_token || '';
  } catch (exception) {
    error.value = exception instanceof Error ? exception.message : 'Unable to load page.';
  } finally {
    loading.value = false;
  }
}

function editPage(value) {
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
  body.append('is_published', page.value.is_published ? '1' : '0');
  body.append('sort_order', String(page.value.sort_order ?? 1));
  body.append('publish_at', localDateTimeValueToIso(page.value.publish_at) ?? page.value.publish_at);
  body.append('unpublish_at', localDateTimeValueToIso(page.value.unpublish_at) ?? page.value.unpublish_at);

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
  if (!value?.id || deleting.value || !window.confirm('Delete this page?')) return;

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
    <Teleport v-if="formMode" to="#admin-page-actions">
      <VfButton type="submit" form="pages-page-form" :loading="saving" :disabled="loading || deleting">
        {{ saving ? 'Saving...' : 'Save page' }}
      </VfButton>
      <VfButton variant="secondary" :disabled="saving || deleting" @click="backToPages">
        Back
      </VfButton>
    </Teleport>
    <Teleport v-else to="#admin-page-actions">
      <VfButton variant="primary" :disabled="loading || saving" @click="newPage">New page</VfButton>
    </Teleport>

    <VfAlert v-if="error" tone="danger" title="Pages">
      {{ error }}
    </VfAlert>
    <VfAlert v-if="success" tone="success" title="Pages">
      {{ success }}
    </VfAlert>

    <div v-if="!formMode" class="pages-screen__list">
      <VfDataTable
        :columns="columns"
        :visible-column-keys="visibleColumns"
        :rows="pages"
        row-key="id"
        striped
        column-dividers
        :loading="loading"
        pagination
        pagination-mode="manual"
        :page="tablePage"
        :page-size="pageSize"
        :total-rows="totalRows"
        empty-text="No pages found"
        @update:page="tablePage = $event"
        @update:page-size="pageSize = $event"
      >
        <template #header-actions>
          <VfDropdown placement="bottom-start" :close-on-select="false">
            <template #trigger>
              <VfIconButton
                :icon="icons.gear"
                variant="ghost"
                size="sm"
                aria-label="Configure columns"
                title="Configure columns"
                :disabled="preferencesSaving"
              />
            </template>
            <div class="pages-screen__column-select-all">
              <VfCheckbox
                label="All columns"
                :model-value="visibleColumns.length === columns.length"
                :disabled="preferencesSaving"
                @update:model-value="toggleAllColumns"
              />
            </div>
            <VfCheckbox
              v-for="column in columns"
              :key="column.key"
              :model-value="visibleColumns.includes(column.key)"
              :label="columnLabels[column.key]"
              :disabled="column.key === 'actions' || preferencesSaving"
              @update:model-value="toggleColumn(column.key, $event)"
            />
          </VfDropdown>
        </template>
        <template #cell-actions="{ row }">
          <VfDropdown placement="bottom-start">
            <template #trigger>
              <VfIconButton
                :icon="icons.bars"
                variant="ghost"
                size="sm"
                :aria-label="`Actions for ${row.title}`"
                :title="`Actions for ${row.title}`"
              />
            </template>
            <VfMenu>
              <VfMenuItem label="Edit" :icon="icons.pencil" @select="editPage(row)" />
              <VfMenuItem
                v-if="row.is_published"
                label="View page"
                :icon="icons.externalLink"
                :href="publicPageUrl(row)"
                target="_blank"
                rel="noopener noreferrer"
              />
              <VfMenuItem label="Delete" :icon="icons.trash" tone="danger" @select="deletePage(row)" />
            </VfMenu>
          </VfDropdown>
        </template>
        <template #cell-title="{ row }">
          <a class="pages-screen__title-link" :href="`/admin/pages/${row.id}/edit`">
            <strong>{{ row.title }}</strong>
          </a>
        </template>
        <template #cell-slug="{ value }">
          /pages/{{ value }}
        </template>
        <template #cell-is_published="{ value }">
          <span :class="['pages-screen__status', { 'pages-screen__status--published': value }]">
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
    </VfDataTable>
    </div>

    <form id="pages-page-form" v-else class="pages-screen__form" novalidate @submit.prevent="savePage">
      <VfCard>
        <div class="pages-screen__fields">
          <VfTabs v-model="activeTab" :items="formTabs">
            <template #panel="{ activeValue }">
              <div v-if="activeValue === 'general'" class="pages-screen__tab-fields">
                <VfField class="pages-screen__published-field" label="Published">
                  <template #default="{ controlId }">
                    <VfCheckbox :id="controlId" v-model="page.is_published" :disabled="saving" />
                  </template>
                </VfField>

                <VfField class="pages-screen__publication-field" label="Active from" :error="firstError('publish_at')">
                  <template #default="{ controlId, describedBy, invalid }">
                    <VfDatePicker
                      :id="controlId"
                      v-model="page.publish_at"
                      show-time
                      clearable
                      :aria-describedby="describedBy"
                      :invalid="invalid"
                      :disabled="saving"
                    />
                  </template>
                </VfField>

                <VfField class="pages-screen__publication-field" label="Active until" :error="firstError('unpublish_at')">
                  <template #default="{ controlId, describedBy, invalid }">
                    <VfDatePicker
                      :id="controlId"
                      v-model="page.unpublish_at"
                      show-time
                      clearable
                      :aria-describedby="describedBy"
                      :invalid="invalid"
                      :disabled="saving"
                    />
                  </template>
                </VfField>

                <VfField label="Title" :error="firstError('title')" required>
                  <template #default="{ controlId, describedBy, invalid }">
                    <VfInput :id="controlId" v-model="page.title" :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" required />
                  </template>
                </VfField>

                <VfField label="Slug" :error="firstError('slug')" required>
                  <template #default="{ controlId, describedBy, invalid }">
                    <VfInput :id="controlId" v-model="page.slug" :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" required />
                  </template>
                </VfField>

                <VfField label="Sort order" :error="firstError('sort_order')">
                  <template #default="{ controlId, describedBy, invalid }">
                    <VfInput :id="controlId" v-model="page.sort_order" type="number" min="1" max="1000000" :aria-describedby="describedBy" :invalid="invalid" :disabled="saving" />
                  </template>
                </VfField>

                <VfField label="Content" :error="firstError('content')" required>
                  <template #default="{ controlId, describedBy, invalid }">
                    <VfTextarea
                      :id="controlId"
                      v-model="page.content"
                      :aria-describedby="describedBy"
                      :invalid="invalid"
                      :disabled="saving"
                      rows="10"
                      required
                    />
                  </template>
                </VfField>

              </div>

              <div v-else-if="activeValue === 'seo'" class="pages-screen__tab-fields">
                <VfField label="Meta title" :error="firstError('meta_title')">
                  <template #default="{ controlId, describedBy, invalid }">
                    <VfInput
                      :id="controlId"
                      v-model="page.meta_title"
                      :aria-describedby="describedBy"
                      :invalid="invalid"
                      :disabled="saving"
                    />
                  </template>
                </VfField>

                <VfField label="Meta description" :error="firstError('meta_description')">
                  <template #default="{ controlId, describedBy, invalid }">
                    <VfTextarea
                      :id="controlId"
                      v-model="page.meta_description"
                      :aria-describedby="describedBy"
                      :invalid="invalid"
                      :disabled="saving"
                      rows="5"
                    />
                  </template>
                </VfField>
              </div>
            </template>
          </VfTabs>

        </div>
      </VfCard>
    </form>
  </div>
</template>

<style scoped>
.pages-screen {
  display: grid;
  gap: var(--vf-section-gap);
}

.pages-screen__column-select-all {
  display: flex;
  padding: 0.25rem 0 0.5rem;
  border-block-end: 1px solid var(--vf-color-border);
}

.pages-screen__fields {
  display: grid;
  gap: var(--vf-surface-gap-compact);
}

.pages-screen__tab-fields {
  display: grid;
  gap: var(--vf-section-gap);
  width: 100%;
}

@media (min-width: 1200px) {
  .pages-screen__tab-fields > :deep(.vf-field) {
    grid-column: 1 / -1;
  }

  .pages-screen__tab-fields :deep(.vf-field) {
    grid-template-columns: minmax(14rem, 25%) minmax(0, 1fr);
    column-gap: var(--vf-section-gap);
    align-items: start;
  }

  .pages-screen__tab-fields :deep(.vf-field__label) {
    align-self: start;
    justify-self: end;
    padding-block-start: 0.65rem;
    overflow-wrap: anywhere;
    text-align: end;
  }

  .pages-screen__tab-fields :deep(.vf-field__control),
  .pages-screen__tab-fields :deep(.vf-field__description),
  .pages-screen__tab-fields :deep(.vf-field__error) {
    grid-column: 2;
  }

  .pages-screen__tab-fields :deep(.vf-field__control) {
    grid-row: 1;
  }

  .pages-screen__published-field :deep(.vf-field__label) {
    align-self: center;
    justify-self: end;
    padding-block-start: 0;
  }

}
.pages-screen__status {
  color: var(--vf-color-muted);
}

.pages-screen__status--published {
  color: var(--vf-color-success);
}

.pages-screen__empty {
  color: var(--vf-color-muted);
}

.pages-screen__title-link {
  color: var(--vf-color-text-link);
  text-decoration: none;
}

.pages-screen__title-link:hover {
  text-decoration: underline;
}

</style>
