<script setup>
import { computed } from 'vue';
import { CmButton, CmTable } from '@codemonster-ru/ui-vue';

const props = defineProps({
  columns: { type: Array, required: true },
  rows: { type: Array, default: () => [] },
  rowKey: { type: String, default: 'id' },
  visibleColumnKeys: { type: Array, default: null },
  caption: { type: String, default: '' },
  density: { type: String, default: 'default' },
  striped: { type: Boolean, default: false },
  columnDividers: { type: Boolean, default: false },
  stickyHeader: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  pagination: { type: Boolean, default: false },
  page: { type: Number, default: 1 },
  pageSize: { type: Number, default: 10 },
  totalRows: { type: Number, default: 0 },
  emptyText: { type: String, default: 'No data' },
  loadingText: { type: String, default: 'Loading...' },
});

const emit = defineEmits(['update:page', 'update:pageSize']);
const pageSizes = [10, 25, 50, 100];
const visibleColumns = computed(() => {
  if (!props.visibleColumnKeys) return props.columns;
  const visible = new Set(props.visibleColumnKeys);
  return props.columns.filter((column) => visible.has(column.key));
});
const pageCount = computed(() => Math.max(1, Math.ceil(props.totalRows / props.pageSize)));
const stateText = computed(() => (props.loading ? props.loadingText : props.rows.length === 0 ? props.emptyText : ''));

function cellStyle(column) {
  return {
    inlineSize: column.width,
    minInlineSize: column.minWidth,
    maxInlineSize: column.maxWidth,
    textAlign: column.align === 'center' ? 'center' : column.align === 'end' ? 'end' : undefined,
    verticalAlign: column.verticalAlign,
    whiteSpace: column.nowrap ? 'nowrap' : undefined,
  };
}

function changePage(nextPage) {
  const next = Math.min(pageCount.value, Math.max(1, nextPage));
  if (next !== props.page) emit('update:page', next);
}

function changePageSize(event) {
  emit('update:pageSize', Number(event.target.value));
  if (props.page !== 1) emit('update:page', 1);
}
</script>

<template>
  <div class="app-data-table">
    <CmTable
      :caption="caption"
      :density="density"
      :striped="striped"
      :column-dividers="columnDividers"
      :sticky-header="stickyHeader"
    >
      <template #header>
        <tr>
          <th v-for="column in visibleColumns" :key="column.key" scope="col" :style="cellStyle(column)">
            <slot :name="`header-${column.key}`" :column="column">{{ column.header }}</slot>
          </th>
        </tr>
      </template>
      <tr v-if="stateText">
        <td class="app-data-table__state" :colspan="visibleColumns.length" role="status">{{ stateText }}</td>
      </tr>
      <tr v-for="(row, rowIndex) in stateText ? [] : rows" :key="String(row[rowKey])">
        <td v-for="column in visibleColumns" :key="column.key" :style="cellStyle(column)">
          <slot
            :name="`cell-${column.key}`"
            :row="row"
            :column="column"
            :value="row[column.key]"
            :row-index="rowIndex"
          >{{ row[column.key] ?? '' }}</slot>
        </td>
      </tr>
    </CmTable>

    <nav v-if="pagination" class="app-data-table__pagination" aria-label="Table pagination">
      <label class="app-data-table__page-size">
        <span>Rows per page</span>
        <select :value="pageSize" :disabled="loading" @change="changePageSize">
          <option v-for="size in pageSizes" :key="size" :value="size">{{ size }}</option>
        </select>
      </label>
      <span class="app-data-table__page-summary" aria-live="polite">Page {{ page }} of {{ pageCount }}</span>
      <div class="app-data-table__page-actions">
        <CmButton variant="secondary" size="sm" :disabled="loading || page <= 1" @click="changePage(page - 1)">Previous</CmButton>
        <CmButton variant="secondary" size="sm" :disabled="loading || page >= pageCount" @click="changePage(page + 1)">Next</CmButton>
      </div>
    </nav>
  </div>
</template>

<style scoped>
.app-data-table { display: grid; gap: var(--cm-space-3); }
.app-data-table__state { padding: var(--cm-space-6); color: var(--cm-color-text-muted); text-align: center; }
.app-data-table__pagination { display: flex; align-items: center; justify-content: flex-end; gap: var(--cm-space-3); flex-wrap: wrap; }
.app-data-table__page-size { display: flex; align-items: center; gap: var(--cm-space-2); color: var(--cm-color-text-muted); }
.app-data-table__page-size select { min-height: var(--cm-control-height-sm); padding-inline: var(--cm-space-2); color: var(--cm-color-text-primary); background: var(--cm-color-background-surface); border: var(--cm-border-width) solid var(--cm-color-border-default); border-radius: var(--cm-radius-control-tight); }
.app-data-table__page-summary { color: var(--cm-color-text-muted); }
.app-data-table__page-actions { display: flex; gap: var(--cm-space-2); }
</style>
