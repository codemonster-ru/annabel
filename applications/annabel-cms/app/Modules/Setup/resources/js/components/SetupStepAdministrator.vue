<script setup>
import { VfField } from '@codemonster-ru/vueforge-core/field';
import { VfInput } from '@codemonster-ru/vueforge-core/input';

const props = defineProps({
  adminUsername: {
    type: String,
    default: '',
  },
  adminEmail: {
    type: String,
    default: '',
  },
  adminPassword: {
    type: String,
    default: '',
  },
  adminPasswordConfirmation: {
    type: String,
    default: '',
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits([
  'update:adminUsername',
  'update:adminEmail',
  'update:adminPassword',
  'update:adminPasswordConfirmation',
  'clearErrors',
]);

const errorFields = {
  adminUsername: 'admin_username',
  adminEmail: 'admin_email',
  adminPassword: 'admin_password',
  adminPasswordConfirmation: 'admin_password_confirmation',
};

function firstError(field) {
  const messages = props.errors[field];

  return Array.isArray(messages) && messages.length > 0 ? messages[0] : '';
}

function update(field, value) {
  emit('clearErrors', errorFields[field] || field);
  emit(`update:${field}`, value);
}
</script>

<template>
  <div class="setup-step">
    <p class="setup-muted">
      Create the first administrator account for signing in after installation.
    </p>

    <div class="setup-fields setup-fields--credentials">
      <VfField label="Username" :error="firstError('admin_username')" required>
        <template #default="{ controlId, describedBy, invalid }">
          <VfInput
            :id="controlId"
            :model-value="adminUsername"
            type="text"
            placeholder="admin"
            autocomplete="username"
            required
            :aria-describedby="describedBy"
            :invalid="invalid"
            @update:model-value="update('adminUsername', $event)"
          />
        </template>
      </VfField>

      <VfField label="Email" :error="firstError('admin_email')" required>
        <template #default="{ controlId, describedBy, invalid }">
          <VfInput
            :id="controlId"
            :model-value="adminEmail"
            type="email"
            placeholder="admin@example.com"
            autocomplete="email"
            required
            :aria-describedby="describedBy"
            :invalid="invalid"
            @update:model-value="update('adminEmail', $event)"
          />
        </template>
      </VfField>
    </div>

    <div class="setup-fields setup-fields--credentials">
      <VfField label="Password" :error="firstError('admin_password')" required>
        <template #default="{ controlId, describedBy, invalid }">
          <VfInput
            :id="controlId"
            :model-value="adminPassword"
            type="password"
            placeholder="Enter password"
            autocomplete="new-password"
            password-reveal
            required
            :aria-describedby="describedBy"
            :invalid="invalid"
            @update:model-value="update('adminPassword', $event)"
          />
        </template>
      </VfField>

      <VfField
        label="Confirm password"
        :error="firstError('admin_password_confirmation')"
        required
      >
        <template #default="{ controlId, describedBy, invalid }">
          <VfInput
            :id="controlId"
            :model-value="adminPasswordConfirmation"
            type="password"
            placeholder="Repeat password"
            autocomplete="new-password"
            password-reveal
            required
            :aria-describedby="describedBy"
            :invalid="invalid"
            @update:model-value="update('adminPasswordConfirmation', $event)"
          />
        </template>
      </VfField>
    </div>
  </div>
</template>
