<script setup>
import { VfCheckbox } from '@codemonster-ru/vueforge-core/checkbox';
import { VfFieldset } from '@codemonster-ru/vueforge-core/fieldset';
import { VfPanel } from '@codemonster-ru/vueforge-core/panel';

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

    <VfPanel>
      <pre class="setup-license">{{ agreement }}</pre>
    </VfPanel>

    <VfFieldset :error="error" v-slot="{ describedBy, invalid }">
      <VfCheckbox
        class="setup-license-confirm"
        :model-value="accepted"
        :aria-describedby="describedBy"
        :invalid="invalid"
        label="I have read and accept the license terms."
        @update:model-value="updateAccepted"
      />
    </VfFieldset>
  </div>
</template>
