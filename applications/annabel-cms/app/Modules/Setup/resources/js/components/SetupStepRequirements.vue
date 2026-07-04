<script setup>
import { computed } from 'vue';
import { icons, VueIconify } from '@codemonster-ru/vueforge-icons';
import { VfAlert } from '@codemonster-ru/vueforge-core/alert';
import { VfDataTable } from '@codemonster-ru/vueforge-core/data-table';
import { VfLink } from '@codemonster-ru/vueforge-core/link';

const filesGroupName = 'Files and folders';
const phpConfigurationGroupName = 'PHP configuration';

const requirementColumns = [
  { key: 'label', header: 'Parameter' },
  { key: 'expected', header: 'Required' },
  { key: 'actual', header: 'Current value' },
];

const filesystemRequirementColumns = [
  { key: 'label', header: 'Parameter' },
  { key: 'actual', header: 'Current value' },
];

const recommendationColumns = [
  { key: 'label', header: 'Parameter' },
  { key: 'expected', header: 'Recommended' },
  { key: 'actual', header: 'Current value' },
];

const props = defineProps({
  checks: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: '',
  },
  passed: {
    type: Boolean,
    default: false,
  },
});

const hasRecommendedWarnings = computed(() => (
  props.checks.some((check) => check.severity === 'recommended' && !check.passed)
));

const groupedChecks = computed(() => {
  const groups = [];

  for (const check of props.checks) {
    const name = check.group;
    let group = groups[groups.length - 1];

    if (!group || group.name !== name) {
      group = {
        name,
        checks: [],
      };
      groups.push(group);
    }

    group.checks.push(check);
  }

  return groups;
});

function columnsForGroup(group) {
  if (group.name === filesGroupName) {
    return filesystemRequirementColumns;
  }

  if (group.name === phpConfigurationGroupName) {
    return recommendationColumns;
  }

  return requirementColumns;
}

function currentValueClass(row) {
  if (row.passed) {
    return 'setup-requirement-current--passed';
  }

  return row.severity === 'recommended'
    ? 'setup-requirement-current--warning'
    : 'setup-requirement-current--failed';
}
</script>

<template>
  <div class="setup-step">
    <p class="setup-muted">
      The installer will verify that the current server can run the installation safely.
    </p>

    <div class="setup-requirements" :aria-busy="loading ? 'true' : 'false'">
      <section v-for="group in groupedChecks" :key="group.name" class="setup-requirements-group">
        <h3 class="setup-requirements-group__title">{{ group.name }}</h3>

        <VfDataTable
          class="setup-requirements-table"
          :columns="columnsForGroup(group)"
          :rows="group.checks"
          :loading="loading"
          row-key="id"
          density="compact"
          column-dividers
        >
          <template #cell-label="{ row, value }">
            <span class="setup-requirement-parameter">
              <VfLink
                v-if="row.documentationUrl"
                class="setup-requirement-doc-link"
                :href="row.documentationUrl"
                target="_blank"
                underline="hover"
              >
                <span>{{ value }}</span>
                <VueIconify
                  class="setup-requirement-doc-link__icon"
                  :icon="icons.externalLink"
                  aria-hidden="true"
                />
              </VfLink>
              <span v-else>{{ value }}</span>
              <template v-if="row.path">
                <span>: </span>
                <code class="setup-requirement-path">{{ row.path }}</code>
              </template>
            </span>
          </template>

          <template #cell-actual="{ row, value }">
            <span
              class="setup-requirement-current"
              :class="currentValueClass(row)"
            >
              {{ value }}
            </span>
          </template>
        </VfDataTable>
      </section>

      <p v-if="loading && checks.length === 0" class="setup-muted">
        Checking the current environment...
      </p>
    </div>

    <VfAlert v-if="error" tone="danger" title="Unable to check requirements">
      {{ error }}
    </VfAlert>

    <VfAlert v-else-if="!passed && checks.length > 0" tone="danger" title="Environment is not ready">
      Resolve the failed checks before continuing.
    </VfAlert>

    <VfAlert
      v-else-if="hasRecommendedWarnings"
      tone="warn"
      title="Recommended settings need attention"
    >
      You can continue, but review the highlighted settings before production use.
    </VfAlert>
  </div>
</template>
