<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
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

const props = defineProps({
  boot: {
    type: Object,
    default: () => ({}),
  },
});

const stepOrder = ['welcome', 'requirements', 'license', 'database', 'administrator', 'review'];
const stepTitles = {
  welcome: 'Welcome',
  license: 'License',
  requirements: 'Requirements',
  database: 'Database',
  administrator: 'Administrator',
  review: 'Install',
};

const stepHeadings = {
  welcome: 'Welcome to Annabel CMS',
  license: 'License agreement',
  requirements: 'Check system requirements',
  database: 'Database connection',
  administrator: 'Administrator account',
  review: 'Ready to install',
};

const minimumCheckLoadingMs = 350;
const minimumInstallLoadingMs = 1200;

const csrfToken = ref(props.boot.csrfToken || '');
const setupDbHost = ref('');
const setupDbPort = ref('');
const setupDbDatabase = ref('');
const setupDbUsername = ref('');
const setupDbPassword = ref('');
const setupAdminUsername = ref('');
const setupAdminEmail = ref('');
const setupAdminPassword = ref('');
const setupAdminPasswordConfirmation = ref('');
const licenseAccepted = ref(false);
const setupError = ref('');
const setupErrorStep = ref('');
const setupErrors = ref({});
const stepErrors = ref({});
const currentStep = ref('welcome');
const installationComplete = ref(false);
const adminUrl = ref('/admin/login');
const siteUrl = ref('/');
const loading = ref(false);
const databaseChecking = ref(false);
const requirementsLoading = ref(false);
const requirementsLoaded = ref(false);
const requirementsPassed = ref(false);
const requirementsChecks = ref([]);
const requirementsError = ref('');

const canGoBack = computed(() => !installationComplete.value && stepIndex(currentStep.value) > 0);
const canGoNext = computed(() => stepIndex(currentStep.value) < stepOrder.length - 1);
const busy = computed(() => loading.value || databaseChecking.value || requirementsLoading.value);
const currentStepError = computed(() => (
  setupErrorStep.value === currentStep.value ? setupError.value : ''
));
const setupErrorTitle = computed(() => (
  currentStep.value === 'database' ? 'Database check failed' : 'Installation failed'
));

function setStepError(message, step = currentStep.value) {
  setupError.value = message;
  setupErrorStep.value = step;
}

function clearSetupError(step = currentStep.value) {
  if (!step || setupErrorStep.value === step) {
    setupError.value = '';
    setupErrorStep.value = '';
  }
}

function hasServerFieldErrors(payload) {
  return payload
    && payload.errors
    && typeof payload.errors === 'object'
    && Object.keys(payload.errors).length > 0;
}

const currentStepTitle = computed(() => (
  installationComplete.value ? 'Installation complete' : stepHeadings[currentStep.value] || ''
));

function firstError(errors, field) {
  const source = errors && typeof errors === 'object' && 'value' in errors
    ? errors.value
    : errors || {};
  const messages = source[field];

  return Array.isArray(messages) && messages.length > 0
    ? messages[0]
    : '';
}

function stepIndex(step) {
  return stepOrder.indexOf(step);
}

function firstStepFieldError(step) {
  const fieldErrors = stepErrors.value[step] || {};

  for (const field of Object.keys(fieldErrors)) {
    const message = firstError(fieldErrors, field);

    if (message) {
      return message;
    }
  }

  return '';
}

function clearStepErrors(step) {
  stepErrors.value = {
    ...stepErrors.value,
    [step]: {},
  };
}

function clearStepFieldError(step, field) {
  const errors = { ...(stepErrors.value[step] || {}) };
  delete errors[field];
  clearSetupError(step);

  stepErrors.value = {
    ...stepErrors.value,
    [step]: errors,
  };
}

function validateLicenseStep(storeErrors = true) {
  const errors = {};

  if (!licenseAccepted.value) {
    errors.license_agreement = ['You must accept the license agreement to continue.'];
  }

  if (storeErrors) {
    stepErrors.value = {
      ...stepErrors.value,
      license: errors,
    };
  }

  return Object.keys(errors).length === 0;
}

function validateAdministratorStep(storeErrors = true) {
  const errors = {};

  if (!setupAdminUsername.value.trim()) {
    errors.admin_username = ['Username is required.'];
  } else if (setupAdminUsername.value.trim().length < 3) {
    errors.admin_username = ['Username must be at least 3 characters.'];
  } else if (!/^[A-Za-z0-9][A-Za-z0-9_-]{2,59}$/.test(setupAdminUsername.value.trim())) {
    errors.admin_username = ['Use letters, numbers, underscores, or hyphens. Start with a letter or number.'];
  }

  if (!setupAdminEmail.value.trim()) {
    errors.admin_email = ['Email is required.'];
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(setupAdminEmail.value.trim())) {
    errors.admin_email = ['Enter a valid email address.'];
  }

  if (!setupAdminPassword.value) {
    errors.admin_password = ['Password is required.'];
  } else if (setupAdminPassword.value.length < 8) {
    errors.admin_password = ['Password must be at least 8 characters.'];
  }

  if (!setupAdminPasswordConfirmation.value) {
    errors.admin_password_confirmation = ['Confirm the password.'];
  } else if (setupAdminPassword.value !== setupAdminPasswordConfirmation.value) {
    errors.admin_password_confirmation = ['Passwords do not match.'];
  }

  if (storeErrors) {
    stepErrors.value = {
      ...stepErrors.value,
      administrator: errors,
    };
  }

  return Object.keys(errors).length === 0;
}

function validateStep(step, storeErrors = true) {
  if (step === 'license') {
    return validateLicenseStep(storeErrors);
  }

  if (step === 'requirements') {
    return validateRequirementsStep(storeErrors);
  }

  if (step === 'administrator') {
    return validateAdministratorStep(storeErrors);
  }

  return true;
}

function validateCurrentStep() {
  return validateStep(currentStep.value, true);
}

function validateRequirementsStep(storeErrors = true) {
  const errors = {};

  if (!requirementsLoaded.value) {
    errors.requirements = ['Run the system requirements check before continuing.'];
  } else if (!requirementsPassed.value) {
    errors.requirements = ['Resolve failed system requirements before continuing.'];
  }

  if (storeErrors) {
    stepErrors.value = {
      ...stepErrors.value,
      requirements: errors,
    };
  }

  return Object.keys(errors).length === 0;
}

async function goToStep(step) {
  const targetIndex = stepIndex(step);

  if (targetIndex === -1) {
    return;
  }

  const currentIndex = stepIndex(currentStep.value);

  if (targetIndex > currentIndex) {
    for (let index = currentIndex; index < targetIndex; index += 1) {
      const candidate = stepOrder[index];

      if (candidate === 'database') {
        if (!await validateDatabaseConnection()) {
          currentStep.value = candidate;
          return;
        }

        continue;
      }

      if (!validateStep(candidate, true)) {
        currentStep.value = candidate;
        return;
      }
    }
  }

  currentStep.value = step;
}

async function goToNextStep() {
  if (currentStep.value === 'database') {
    if (!await validateDatabaseConnection()) {
      return;
    }
  } else if (!validateCurrentStep()) {
    return;
  }

  const index = stepIndex(currentStep.value);

  if (index < stepOrder.length - 1) {
    currentStep.value = stepOrder[index + 1];
  }
}

async function validateDatabaseConnection() {
  const startedAt = performance.now();

  databaseChecking.value = true;

  const body = new FormData();
  body.append('_token', csrfToken.value);
  body.append('db_host', setupDbHost.value);
  body.append('db_port', setupDbPort.value);
  body.append('db_database', setupDbDatabase.value);
  body.append('db_username', setupDbUsername.value);
  body.append('db_password', setupDbPassword.value);

  try {
    const response = await fetch('/setup/database', {
      method: 'POST',
      headers: { Accept: 'application/json' },
      body,
      credentials: 'same-origin',
    });
    const payload = await response.json();

    if (!response.ok) {
      if (response.status === 422 && hasServerFieldErrors(payload)) {
        applyServerErrors(payload.errors);
        clearSetupError('database');
        return false;
      }

      setStepError(payload.message || 'Database check failed.', 'database');
      return false;
    }

    clearSetupError('database');
    return true;
  } catch (e) {
    setStepError('Unable to check the database connection. Please try again.', 'database');
    return false;
  } finally {
    const elapsed = performance.now() - startedAt;

    if (elapsed < minimumCheckLoadingMs) {
      await wait(minimumCheckLoadingMs - elapsed);
    }

    databaseChecking.value = false;
  }
}

function goToPreviousStep() {
  const index = stepIndex(currentStep.value);

  if (index > 0) {
    currentStep.value = stepOrder[index - 1];
  }
}

function visit(url) {
  window.location.assign(url);
}

function applyServerErrors(errors) {
  setupErrors.value = errors || {};

  const databaseErrors = {};
  const administratorErrors = {};

  for (const [field, messages] of Object.entries(setupErrors.value)) {
    if (field.startsWith('db_')) {
      databaseErrors[field] = messages;
    }

    if (field.startsWith('admin_')) {
      administratorErrors[field] = messages;
    }
  }

  stepErrors.value = {
    ...stepErrors.value,
    database: databaseErrors,
    administrator: administratorErrors,
  };

  if (Object.keys(databaseErrors).length > 0) {
    currentStep.value = 'database';
    return;
  }

  if (Object.keys(administratorErrors).length > 0) {
    currentStep.value = 'administrator';
  }
}

async function install() {
  if (installationComplete.value) {
    return;
  }

  if (!validateRequirementsStep(true) || !validateAdministratorStep(true)) {
    if (!validateRequirementsStep(false)) {
      currentStep.value = 'requirements';
    } else {
      currentStep.value = 'administrator';
    }

    return;
  }

  clearSetupError();
  setupErrors.value = {};
  loading.value = true;
  const startedAt = performance.now();

  const body = new FormData();
  body.append('_token', csrfToken.value);
  body.append('license_accepted', licenseAccepted.value ? '1' : '0');
  body.append('db_host', setupDbHost.value);
  body.append('db_port', setupDbPort.value);
  body.append('db_database', setupDbDatabase.value);
  body.append('db_username', setupDbUsername.value);
  body.append('db_password', setupDbPassword.value);
  body.append('admin_username', setupAdminUsername.value);
  body.append('admin_email', setupAdminEmail.value);
  body.append('admin_password', setupAdminPassword.value);
  body.append('admin_password_confirmation', setupAdminPasswordConfirmation.value);

  try {
    const response = await fetch('/setup', {
      method: 'POST',
      headers: { Accept: 'application/json' },
      body,
      credentials: 'same-origin',
    });
    const payload = await response.json();

    if (!response.ok) {
      if (response.status === 422 && hasServerFieldErrors(payload)) {
        applyServerErrors(payload.errors);
        clearSetupError();
        return;
      }

      setStepError(payload.message || 'Installation failed.');
      return;
    }

    clearSetupError();
    adminUrl.value = payload.redirect || '/admin/login';
    installationComplete.value = true;
  } catch (e) {
    setStepError('Unable to complete installation. Please try again.');
  } finally {
    const elapsed = performance.now() - startedAt;

    if (elapsed < minimumInstallLoadingMs) {
      await wait(minimumInstallLoadingMs - elapsed);
    }

    loading.value = false;
  }
}

async function submitPrimaryAction() {
  if (installationComplete.value) {
    return;
  }

  if (busy.value) {
    return;
  }

  if (canGoNext.value) {
    await goToNextStep();
    return;
  }

  await install();
}

async function handlePrimaryActionKeydown(event) {
  if (!canHandlePrimaryActionKeydown(event)) {
    return;
  }

  event.preventDefault();
  await submitPrimaryAction();
}

function canHandlePrimaryActionKeydown(event) {
  const target = event.target;

  if (
    event.defaultPrevented
    || event.key !== 'Enter'
    || event.repeat
    || event.altKey
    || event.ctrlKey
    || event.metaKey
    || event.shiftKey
    || event.isComposing
  ) {
    return false;
  }

  if (!(target instanceof HTMLElement)) {
    return true;
  }

  const tagName = target.tagName.toLowerCase();

  return tagName !== 'textarea'
    && tagName !== 'button'
    && tagName !== 'a'
    && !target.isContentEditable;
}

async function handleWindowPrimaryActionKeydown(event) {
  const target = event.target;

  if (
    target instanceof HTMLElement
    && target.closest('.vf-setup-layout')
    && target !== document.body
    && target !== document.documentElement
  ) {
    return;
  }

  if (!canHandlePrimaryActionKeydown(event)) {
    return;
  }

  event.preventDefault();
  await submitPrimaryAction();
}

async function loadRequirements() {
  if (requirementsLoading.value) {
    return;
  }

  const startedAt = performance.now();

  requirementsLoading.value = true;
  requirementsError.value = '';
  clearStepErrors('requirements');

  try {
    const response = await fetch('/setup/requirements', {
      method: 'GET',
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
    });
    const payload = await response.json();

    if (!response.ok) {
      requirementsError.value = payload.message || 'Unable to check system requirements.';
      return;
    }

    requirementsLoaded.value = true;
    requirementsPassed.value = Boolean(payload.passed);
    requirementsChecks.value = Array.isArray(payload.checks) ? payload.checks : [];
  } catch (e) {
    requirementsError.value = 'Unable to check system requirements. Please try again.';
    requirementsLoaded.value = false;
    requirementsPassed.value = false;
    requirementsChecks.value = [];
  } finally {
    const elapsed = performance.now() - startedAt;

    if (elapsed < minimumCheckLoadingMs) {
      await wait(minimumCheckLoadingMs - elapsed);
    }

    requirementsLoading.value = false;
  }
}

function wait(ms) {
  return new Promise((resolve) => {
    window.setTimeout(resolve, ms);
  });
}

const stepItems = computed(() => stepOrder.map((step) => ({
  value: step,
  label: stepTitles[step],
  disabled: busy.value || installationComplete.value,
})));

watch(currentStep, (step) => {
  if (step === 'requirements' && !requirementsLoaded.value && !requirementsLoading.value) {
    loadRequirements();
  }
});

onMounted(() => {
  window.addEventListener('keydown', handleWindowPrimaryActionKeydown);
});

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleWindowPrimaryActionKeydown);
});
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
