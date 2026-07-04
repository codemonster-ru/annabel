<script setup>
import { VfAlert } from '@codemonster-ru/vueforge-core/alert';
import { VfField } from '@codemonster-ru/vueforge-core/field';
import { VfInput } from '@codemonster-ru/vueforge-core/input';

const props = defineProps({
  dbHost: {
    type: String,
    default: '',
  },
  dbPort: {
    type: String,
    default: '',
  },
  dbDatabase: {
    type: String,
    default: '',
  },
  dbUsername: {
    type: String,
    default: '',
  },
  dbPassword: {
    type: String,
    default: '',
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits([
  'update:dbHost',
  'update:dbPort',
  'update:dbDatabase',
  'update:dbUsername',
  'update:dbPassword',
  'clearErrors',
]);

const errorFields = {
  dbHost: 'db_host',
  dbPort: 'db_port',
  dbDatabase: 'db_database',
  dbUsername: 'db_username',
  dbPassword: 'db_password',
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
      Enter the connection details for an existing database.
    </p>

    <VfAlert tone="info" title="Database permissions">
      The database user must be able to create, modify, and remove tables.
    </VfAlert>

    <div class="setup-fields">
      <div class="setup-fields setup-fields--connection">
        <VfField label="Host" :error="firstError('db_host')" required>
          <template #default="{ controlId, describedBy, invalid }">
            <VfInput
              :id="controlId"
              :model-value="dbHost"
              type="text"
              placeholder="localhost"
              required
              :aria-describedby="describedBy"
              :invalid="invalid"
              @update:model-value="update('dbHost', $event)"
            />
          </template>
        </VfField>

        <VfField label="Port" :error="firstError('db_port')" required>
          <template #default="{ controlId, describedBy, invalid }">
            <VfInput
              :id="controlId"
              :model-value="dbPort"
              type="text"
              placeholder="3306"
              required
              :aria-describedby="describedBy"
              :invalid="invalid"
              @update:model-value="update('dbPort', $event)"
            />
          </template>
        </VfField>
      </div>

      <VfField label="Database" :error="firstError('db_database')" required>
        <template #default="{ controlId, describedBy, invalid }">
          <VfInput
              :id="controlId"
              :model-value="dbDatabase"
              type="text"
            placeholder="annabel_cms"
            required
            :aria-describedby="describedBy"
            :invalid="invalid"
            @update:model-value="update('dbDatabase', $event)"
          />
        </template>
      </VfField>

      <div class="setup-fields setup-fields--credentials">
        <VfField label="Username" :error="firstError('db_username')" required>
          <template #default="{ controlId, describedBy, invalid }">
            <VfInput
              :id="controlId"
              :model-value="dbUsername"
              type="text"
              placeholder="annabel_user"
              required
              :aria-describedby="describedBy"
              :invalid="invalid"
              @update:model-value="update('dbUsername', $event)"
            />
          </template>
        </VfField>

        <VfField label="Password" v-slot="{ controlId, describedBy, invalid }">
          <VfInput
            :id="controlId"
            :model-value="dbPassword"
            type="password"
            :aria-describedby="describedBy"
            :invalid="invalid"
            password-reveal
            @update:model-value="update('dbPassword', $event)"
          />
        </VfField>
      </div>
    </div>
  </div>
</template>
