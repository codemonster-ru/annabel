<script setup>
import { computed } from 'vue';
import { CmCheckbox } from '@codemonster-ru/ui-vue';
import { icons } from '@codemonster-ru/vueforge-icons';
import AppDropdown from './AppDropdown.vue';
import AppIconButton from './AppIconButton.vue';

const props = defineProps({
  columns: {
    type: Array,
    required: true,
  },
  modelValue: {
    type: Array,
    default: undefined,
  },
  requiredColumnKeys: {
    type: Array,
    default: () => [],
  },
  disabled: Boolean,
});

const emit = defineEmits(['update:modelValue']);
const columnKeys = computed(() => props.columns.map(({ key }) => key));
const requiredKeys = computed(() => new Set(props.requiredColumnKeys.filter((key) => columnKeys.value.includes(key))));
const visibleKeys = computed(() => {
  const selected = new Set(props.modelValue ?? columnKeys.value);
  return columnKeys.value.filter((key) => selected.has(key) || requiredKeys.value.has(key));
});
const optionalColumns = computed(() => props.columns.filter(({ key }) => !requiredKeys.value.has(key)));
const selectedOptionalCount = computed(() => optionalColumns.value.filter(({ key }) => visibleKeys.value.includes(key)).length);
const allSelected = computed(() => selectedOptionalCount.value === optionalColumns.value.length);
const partiallySelected = computed(() => selectedOptionalCount.value > 0 && !allSelected.value);

function commit(keys) {
  const selected = new Set([...keys, ...requiredKeys.value]);
  emit('update:modelValue', columnKeys.value.filter((key) => selected.has(key)));
}

function toggleAll(checked) {
  commit(checked ? columnKeys.value : requiredKeys.value);
}

function toggleColumn(key, checked) {
  const selected = new Set(visibleKeys.value);
  checked ? selected.add(key) : selected.delete(key);
  commit(selected);
}
</script>

<template>
  <AppDropdown placement="bottom-start">
    <template #trigger>
      <AppIconButton
        :icon="icons.gear"
        variant="ghost"
        size="sm"
        aria-label="Configure columns"
        title="Configure columns"
        :disabled="disabled"
      />
    </template>
    <div class="app-column-chooser">
      <CmCheckbox
        :model-value="allSelected"
        :indeterminate="partiallySelected"
        label="All columns"
        :disabled="disabled || optionalColumns.length === 0"
        @update:model-value="toggleAll"
      />
      <CmCheckbox
        v-for="column in columns"
        :key="column.key"
        :model-value="visibleKeys.includes(column.key)"
        :label="column.header || column.key"
        :disabled="disabled || requiredKeys.has(column.key)"
        @update:model-value="toggleColumn(column.key, $event)"
      />
    </div>
  </AppDropdown>
</template>
