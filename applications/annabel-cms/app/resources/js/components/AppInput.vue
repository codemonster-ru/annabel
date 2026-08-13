<script setup>
import { computed, ref, useAttrs } from 'vue';

defineOptions({ inheritAttrs: false });
const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  type: {
    type: String,
    required: true,
    validator: (value) => ['datetime-local', 'number', 'password'].includes(value),
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
  invalid: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
  required: { type: Boolean, default: false },
  passwordReveal: { type: Boolean, default: false },
});
const emit = defineEmits(['input', 'update:modelValue']);
const attrs = useAttrs();
const passwordVisible = ref(false);
const inputType = computed(() => (props.type === 'password' && passwordVisible.value ? 'text' : props.type));
const inputClasses = computed(() => [
  'cm-input',
  `cm-input--${props.size}`,
  props.invalid ? 'cm-input--invalid' : null,
  props.passwordReveal ? 'app-input__control--revealable' : null,
  attrs.class,
]);
const inputAttrs = computed(() => {
  const { class: _class, ...rest } = attrs;
  return rest;
});

function updateValue(event) {
  emit('update:modelValue', event.target.value);
  emit('input', event);
}
</script>

<template>
  <span v-if="passwordReveal" class="app-input">
    <input
      v-bind="inputAttrs"
      :class="inputClasses"
      :type="inputType"
      :value="modelValue"
      :disabled="disabled || undefined"
      :readonly="readonly || undefined"
      :required="required || undefined"
      :aria-invalid="invalid ? 'true' : undefined"
      @input="updateValue"
    />
    <button
      class="app-input__reveal"
      type="button"
      :aria-label="passwordVisible ? 'Hide password' : 'Show password'"
      :aria-pressed="passwordVisible ? 'true' : 'false'"
      :disabled="disabled || undefined"
      @click="passwordVisible = !passwordVisible"
    >{{ passwordVisible ? 'Hide' : 'Show' }}</button>
  </span>
  <input
    v-else
    v-bind="inputAttrs"
    :class="inputClasses"
    :type="inputType"
    :value="modelValue"
    :disabled="disabled || undefined"
    :readonly="readonly || undefined"
    :required="required || undefined"
    :aria-invalid="invalid ? 'true' : undefined"
    @input="updateValue"
  />
</template>

<style scoped>
.app-input { position: relative; display: inline-flex; width: 100%; }
.app-input__control--revealable { width: 100%; padding-inline-end: 4.5rem; }
.app-input__reveal { position: absolute; inset-block: var(--cm-space-1); inset-inline-end: var(--cm-space-1); padding-inline: var(--cm-space-2); color: var(--cm-color-text-link); background: transparent; border: 0; border-radius: var(--cm-radius-control-tight); font: inherit; cursor: pointer; }
.app-input__reveal:hover:not(:disabled) { background: var(--cm-color-background-surface-hover); }
.app-input__reveal:focus-visible { outline: var(--cm-border-width-thick) solid var(--cm-color-focus-ring); outline-offset: 0; }
.app-input__reveal:disabled { color: var(--cm-color-text-disabled); cursor: not-allowed; }
</style>
