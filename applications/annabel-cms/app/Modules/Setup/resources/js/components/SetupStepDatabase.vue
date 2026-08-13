<script setup>
import { CmAlert } from '@codemonster-ru/ui-vue';
import { CmField } from '@codemonster-ru/ui-vue';
import { CmInput } from '@codemonster-ru/ui-vue';
import AppInput from '../../../../../resources/js/components/AppInput.vue';

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

    <CmAlert tone="info" title="Database permissions">
      The database user must be able to create, modify, and remove tables.
    </CmAlert>

    <div class="setup-fields">
      <div class="setup-fields setup-fields--connection">
        <CmField label="Host" :error="firstError('db_host')" required>
          <template #default="{ controlId, describedBy, invalid }">
            <CmInput
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
        </CmField>

        <CmField label="Port" :error="firstError('db_port')" required>
          <template #default="{ controlId, describedBy, invalid }">
            <CmInput
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
        </CmField>
      </div>

      <CmField label="Database" :error="firstError('db_database')" required>
        <template #default="{ controlId, describedBy, invalid }">
          <CmInput
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
      </CmField>

      <div class="setup-fields setup-fields--credentials">
        <CmField label="Username" :error="firstError('db_username')" required>
          <template #default="{ controlId, describedBy, invalid }">
            <CmInput
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
        </CmField>

        <CmField label="Password" v-slot="{ controlId, describedBy, invalid }">
          <AppInput
            :id="controlId"
            :model-value="dbPassword"
            type="password"
            :aria-describedby="describedBy"
            :invalid="invalid"
            password-reveal
            @update:model-value="update('dbPassword', $event)"
          />
        </CmField>
      </div>
    </div>
  </div>
</template>
