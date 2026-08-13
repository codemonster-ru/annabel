<script setup>
import { computed, useAttrs, useId } from 'vue';
import { CmField } from '@codemonster-ru/ui-vue';

defineOptions({ inheritAttrs: false });
const props = defineProps({
  controlId: { type: String, default: '' },
  label: { type: String, default: null },
  description: { type: String, default: null },
  error: { type: String, default: null },
  invalid: { type: Boolean, default: false },
  required: { type: Boolean, default: false },
});
const attrs = useAttrs();
const generatedId = `app-field-${useId().replace(/[^a-z0-9-]/gi, '')}`;
const resolvedControlId = computed(() => props.controlId.trim() || generatedId);
</script>

<template>
  <CmField
    v-bind="attrs"
    :control-id="resolvedControlId"
    :label="label"
    :description="description"
    :error="error"
    :invalid="invalid"
    :required="required"
  >
    <template v-if="$slots.label" #label><slot name="label" /></template>
    <template #default="slotProps"><slot v-bind="slotProps" /></template>
    <template v-if="$slots.description" #description><slot name="description" /></template>
    <template v-if="$slots.error" #error><slot name="error" /></template>
  </CmField>
</template>
