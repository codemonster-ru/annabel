<script setup>
import { CmCheckbox } from '@codemonster-ru/ui-vue';
import AppFieldset from './AppFieldset.vue';
import AppPanel from './AppPanel.vue';

const props = defineProps({
  agreement: {
    type: String,
    default: '',
  },
  accepted: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: '',
  },
});

const emit = defineEmits([
  'update:accepted',
  'clear-errors',
]);

function updateAccepted(event) {
  emit('clear-errors');
  emit('update:accepted', event);
}
</script>

<template>
  <div class="setup-step">
    <p class="setup-muted">
      Please review the license terms before continuing.
    </p>

    <AppPanel>
      <pre class="setup-license">{{ agreement }}</pre>
    </AppPanel>

    <AppFieldset :error="error" v-slot="{ describedBy, invalid }">
      <CmCheckbox
        class="setup-license-confirm"
        :model-value="accepted"
        :aria-describedby="describedBy"
        :invalid="invalid"
        label="I have read and accept the license terms."
        @update:model-value="updateAccepted"
      />
    </AppFieldset>
  </div>
</template>
