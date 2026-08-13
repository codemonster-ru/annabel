<script setup>
import { computed } from 'vue';

const props = defineProps({
  value: {
    type: Number,
    default: 0,
  },
  max: {
    type: Number,
    default: 100,
  },
  indeterminate: {
    type: Boolean,
    default: false,
  },
  label: {
    type: String,
    required: true,
  },
});

const normalizedMax = computed(() => (
  Number.isFinite(props.max) && props.max > 0 ? props.max : 100
));
const normalizedValue = computed(() => (
  Number.isFinite(props.value)
    ? Math.min(Math.max(props.value, 0), normalizedMax.value)
    : 0
));
const width = computed(() => `${(normalizedValue.value / normalizedMax.value) * 100}%`);
</script>

<template>
  <div
    class="app-progress-bar"
    :class="{ 'app-progress-bar--indeterminate': indeterminate }"
    role="progressbar"
    :aria-label="label"
    :aria-valuemin="indeterminate ? undefined : 0"
    :aria-valuemax="indeterminate ? undefined : normalizedMax"
    :aria-valuenow="indeterminate ? undefined : normalizedValue"
  >
    <span
      class="app-progress-bar__value"
      :style="{ inlineSize: indeterminate ? undefined : width }"
    />
  </div>
</template>

<style scoped>
.app-progress-bar {
  position: relative;
  overflow: hidden;
  inline-size: 100%;
  block-size: var(--cm-space-2);
  border-radius: var(--cm-radius-round);
  background: var(--cm-color-background-surface-subtle);
}

.app-progress-bar__value {
  display: block;
  block-size: 100%;
  border-radius: inherit;
  background: var(--cm-color-interactive-primary-background);
  transition: inline-size var(--cm-motion-duration-normal) var(--cm-motion-ease-standard);
}

.app-progress-bar--indeterminate .app-progress-bar__value {
  position: absolute;
  inset-block: 0;
  inline-size: 40%;
  animation: app-progress-bar-indeterminate 1.4s ease-in-out infinite;
}

@keyframes app-progress-bar-indeterminate {
  from {
    inset-inline-start: -40%;
  }

  to {
    inset-inline-start: 100%;
  }
}

@media (prefers-reduced-motion: reduce) {
  .app-progress-bar--indeterminate .app-progress-bar__value {
    inline-size: 100%;
    animation: none;
  }
}
</style>
