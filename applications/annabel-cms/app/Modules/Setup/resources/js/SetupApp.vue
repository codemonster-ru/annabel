<script setup>
import { VfThemeProvider } from '@codemonster-ru/vueforge-core';
import { VfAlert } from '@codemonster-ru/vueforge-core/alert';
import { VfButton } from '@codemonster-ru/vueforge-core/button';
import { VfNavMenu } from '@codemonster-ru/vueforge-core/nav-menu';
import { VfSetupLayout } from '@codemonster-ru/vueforge-layouts/setup-layout';
import licenseAgreement from '../content/license-agreement.txt?raw';
import SetupStepAdministrator from './components/SetupStepAdministrator.vue';
import SetupStepDatabase from './components/SetupStepDatabase.vue';
import SetupStepLicense from './components/SetupStepLicense.vue';
import SetupStepRequirements from './components/SetupStepRequirements.vue';
import SetupStepReview from './components/SetupStepReview.vue';
import SetupStepWelcome from './components/SetupStepWelcome.vue';
import { useSetupFlow } from './composables/useSetupFlow';

const props = defineProps({
  boot: {
    type: Object,
    default: () => ({}),
  },
});

const {
  adminUrl,
  busy,
  canGoBack,
  canGoNext,
  clearStepErrors,
  clearStepFieldError,
  currentStep,
  currentStepError,
  currentStepTitle,
  databaseChecking,
  firstError,
  goToNextStep,
  goToPreviousStep,
  goToStep,
  handlePrimaryActionKeydown,
  installationComplete,
  licenseAccepted,
  loadRequirements,
  loading,
  requirementsChecks,
  requirementsError,
  requirementsLoading,
  requirementsPassed,
  setupAdminEmail,
  setupAdminPassword,
  setupAdminPasswordConfirmation,
  setupAdminUsername,
  setupDbDatabase,
  setupDbHost,
  setupDbPassword,
  setupDbPort,
  setupDbUsername,
  setupErrorTitle,
  siteUrl,
  stepErrors,
  stepItems,
  submitPrimaryAction,
  visit,
} = useSetupFlow(props.boot);
</script>

<template>
  <VfThemeProvider>
    <VfSetupLayout
      as="form"
      :title="currentStepTitle"
      method="post"
      action="/setup"
      novalidate
      @submit.prevent="submitPrimaryAction"
      @keydown.enter="handlePrimaryActionKeydown"
    >
      <template #brand>
        <div class="setup-brand">
          <span class="setup-brand__name">Annabel CMS</span>
        </div>
      </template>

      <template #aside>
        <VfNavMenu
          class="setup-nav"
          :items="stepItems"
          :model-value="currentStep"
          aria-label="Installation steps"
          variant="pills"
          @select="goToStep($event.value)"
        />
      </template>

      <div class="setup-form">
        <SetupStepWelcome
          v-if="currentStep === 'welcome'"
        />

        <SetupStepLicense
          v-else-if="currentStep === 'license'"
          :agreement="licenseAgreement"
          :accepted="licenseAccepted"
          :error="firstError(stepErrors.license || {}, 'license_agreement')"
          @update:accepted="licenseAccepted = $event"
          @clear-errors="clearStepErrors('license')"
        />

        <SetupStepRequirements
          v-else-if="currentStep === 'requirements'"
          :checks="requirementsChecks"
          :loading="requirementsLoading"
          :error="requirementsError || firstError(stepErrors.requirements || {}, 'requirements')"
          :passed="requirementsPassed"
        />

        <SetupStepDatabase
          v-else-if="currentStep === 'database'"
          v-model:db-host="setupDbHost"
          v-model:db-port="setupDbPort"
          v-model:db-database="setupDbDatabase"
          v-model:db-username="setupDbUsername"
          v-model:db-password="setupDbPassword"
          :errors="stepErrors.database || {}"
          @clear-errors="clearStepFieldError('database', $event)"
        />

        <SetupStepAdministrator
          v-else-if="currentStep === 'administrator'"
          v-model:admin-username="setupAdminUsername"
          v-model:admin-email="setupAdminEmail"
          v-model:admin-password="setupAdminPassword"
          v-model:admin-password-confirmation="setupAdminPasswordConfirmation"
          :errors="stepErrors.administrator || {}"
          @clear-errors="clearStepFieldError('administrator', $event)"
        />

        <SetupStepReview
          v-else
          :installing="loading"
          :installed="installationComplete"
        />
      </div>

      <VfAlert v-if="currentStepError" class="setup-step-alert" tone="danger" :title="setupErrorTitle">
        {{ currentStepError }}
      </VfAlert>

      <template #actions>
        <template v-if="installationComplete">
          <VfButton type="button" variant="secondary" @click="visit(siteUrl)">
            Open site
          </VfButton>

          <VfButton type="button" @click="visit(adminUrl)">
            Open admin panel
          </VfButton>
        </template>

        <VfButton
          v-if="!installationComplete && canGoBack"
          class="setup-action-back"
          type="button"
          variant="secondary"
          :disabled="busy"
          @click="goToPreviousStep"
        >
          Back
        </VfButton>

        <VfButton
          v-if="!installationComplete && currentStep === 'requirements'"
          type="button"
          variant="secondary"
          :disabled="busy"
          :loading="requirementsLoading"
          @click="loadRequirements"
        >
          <span>Recheck</span>
        </VfButton>

        <VfButton
          v-if="!installationComplete && canGoNext"
          type="button"
          :disabled="busy"
          :loading="databaseChecking && currentStep === 'database'"
          @click="goToNextStep"
        >
          <span>Next</span>
        </VfButton>

        <VfButton v-else-if="!installationComplete" type="submit" :disabled="busy" :loading="loading">
          {{ loading ? 'Installing...' : 'Install' }}
        </VfButton>
      </template>
    </VfSetupLayout>
  </VfThemeProvider>
</template>
