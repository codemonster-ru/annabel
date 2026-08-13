<script setup>
import { useId } from 'vue';
import { CmButton, CmDialog } from '@codemonster-ru/ui-vue';

const props = defineProps({
  open: Boolean,
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: '',
  },
  confirmLabel: {
    type: String,
    default: 'Confirm',
  },
  cancelLabel: {
    type: String,
    default: 'Cancel',
  },
  confirmVariant: {
    type: String,
    default: 'danger',
  },
  loading: Boolean,
  disabled: Boolean,
  closeOnConfirm: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['update:open', 'confirm', 'cancel']);
const id = `app-confirm-${useId().replaceAll(':', '')}`;

function close() {
  if (props.loading) return;
  emit('cancel');
  emit('update:open', false);
}

function confirm() {
  if (props.loading || props.disabled) return;
  emit('confirm');
  if (props.closeOnConfirm) emit('update:open', false);
}
</script>

<template>
  <CmDialog
    :id="id"
    :open="open"
    :title="title"
    :description="description"
    :dismissible="!loading"
    @update:open="$event ? emit('update:open', true) : close()"
  >
    <slot />
    <template #footer>
      <div class="app-confirm-dialog__actions">
        <CmButton variant="secondary" :disabled="loading" :autofocus="!loading" @click="close">
          {{ cancelLabel }}
        </CmButton>
        <CmButton :variant="confirmVariant" :loading="loading" :disabled="disabled" @click="confirm">
          {{ confirmLabel }}
        </CmButton>
      </div>
    </template>
  </CmDialog>
</template>
