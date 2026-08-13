<script setup>
import { computed, useAttrs, useId } from 'vue';

defineOptions({ inheritAttrs: false });
const props = defineProps({
  id: { type: String, default: '' },
  items: { type: Array, required: true },
  modelValue: { type: String, default: null },
});
const emit = defineEmits(['update:modelValue']);
const attrs = useAttrs();
const generatedId = `app-tabs-${useId().replace(/[^a-z0-9-]/gi, '')}`;
const resolvedId = computed(() => props.id.trim() || generatedId);
const rootAttrs = computed(() => {
  const { class: _class, ...rest } = attrs;
  return rest;
});
const enabledItems = computed(() => props.items.filter((item) => !item.disabled));
const activeValue = computed(() => {
  const selected = enabledItems.value.find((item) => item.value === props.modelValue);
  return selected?.value ?? enabledItems.value[0]?.value ?? '';
});

function select(item, focus = false) {
  if (item.disabled) return;
  emit('update:modelValue', item.value);
  if (focus) document.getElementById(`${resolvedId.value}-tab-${item.value}`)?.focus();
}

function move(event, item) {
  const index = enabledItems.value.findIndex(({ value }) => value === item.value);
  if (index < 0) return;
  const last = enabledItems.value.length - 1;
  const nextIndex = event.key === 'Home'
    ? 0
    : event.key === 'End'
      ? last
      : event.key === 'ArrowRight'
        ? (index + 1) % enabledItems.value.length
        : event.key === 'ArrowLeft'
          ? (index - 1 + enabledItems.value.length) % enabledItems.value.length
          : -1;
  if (nextIndex < 0) return;
  event.preventDefault();
  select(enabledItems.value[nextIndex], true);
}
</script>

<template>
  <div v-bind="rootAttrs" :class="['cm-tabs', attrs.class]">
    <div class="cm-tabs__list" role="tablist" aria-orientation="horizontal">
      <button
        v-for="item in items"
        :id="`${resolvedId}-tab-${item.value}`"
        :key="item.value"
        class="cm-tabs__tab"
        type="button"
        role="tab"
        :aria-controls="`${resolvedId}-panel-${item.value}`"
        :aria-selected="activeValue === item.value"
        :tabindex="activeValue === item.value ? 0 : -1"
        :disabled="item.disabled || undefined"
        @click="select(item)"
        @keydown="move($event, item)"
      >{{ item.label }}</button>
    </div>
    <div
      :id="`${resolvedId}-panel-${activeValue}`"
      class="cm-tabs__panel"
      role="tabpanel"
      :aria-labelledby="`${resolvedId}-tab-${activeValue}`"
      tabindex="0"
    >
      <slot name="panel" :active-value="activeValue" />
    </div>
  </div>
</template>
