import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { installCms, loadRequirements as loadRequirementsRequest, validateDatabase } from '../api/setupApi';

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

export function useSetupFlow(boot = {}) {
  const csrfToken = ref(boot.csrfToken || '');
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
  const currentStepTitle = computed(() => (
    installationComplete.value ? 'Installation complete' : stepHeadings[currentStep.value] || ''
  ));
  const stepItems = computed(() => stepOrder.map((step) => ({
    value: step,
    label: stepTitles[step],
    disabled: busy.value || installationComplete.value,
  })));

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

  function validateAdministratorStep(storeErrors = true) {
    const errors = {};
    const username = setupAdminUsername.value.trim();
    const email = setupAdminEmail.value.trim();

    if (!username) {
      errors.admin_username = ['Username is required.'];
    } else if (username.length < 3) {
      errors.admin_username = ['Username must be at least 3 characters.'];
    } else if (!/^[A-Za-z0-9][A-Za-z0-9_-]{2,59}$/.test(username)) {
      errors.admin_username = ['Use letters, numbers, underscores, or hyphens. Start with a letter or number.'];
    }

    if (!email) {
      errors.admin_email = ['Email is required.'];
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
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
    } else if (!validateStep(currentStep.value, true)) {
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

    try {
      const { ok, status, payload } = await validateDatabase(csrfToken.value, databasePayload());

      if (!ok) {
        if (status === 422 && hasServerFieldErrors(payload)) {
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
      await finishMinimumLoading(startedAt, minimumCheckLoadingMs);
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
      currentStep.value = !validateRequirementsStep(false) ? 'requirements' : 'administrator';
      return;
    }

    clearSetupError();
    setupErrors.value = {};
    loading.value = true;
    const startedAt = performance.now();

    try {
      const { ok, status, payload } = await installCms(csrfToken.value, {
        licenseAccepted: licenseAccepted.value,
        database: databasePayload(),
        admin: adminPayload(),
      });

      if (!ok) {
        if (status === 422 && hasServerFieldErrors(payload)) {
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
      await finishMinimumLoading(startedAt, minimumInstallLoadingMs);
      loading.value = false;
    }
  }

  async function submitPrimaryAction() {
    if (installationComplete.value || busy.value) {
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
      const { ok, payload } = await loadRequirementsRequest();

      if (!ok) {
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
      await finishMinimumLoading(startedAt, minimumCheckLoadingMs);
      requirementsLoading.value = false;
    }
  }

  function hasServerFieldErrors(payload) {
    return payload
      && payload.errors
      && typeof payload.errors === 'object'
      && Object.keys(payload.errors).length > 0;
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

  function databasePayload() {
    return {
      host: setupDbHost.value,
      port: setupDbPort.value,
      database: setupDbDatabase.value,
      username: setupDbUsername.value,
      password: setupDbPassword.value,
    };
  }

  function adminPayload() {
    return {
      username: setupAdminUsername.value,
      email: setupAdminEmail.value,
      password: setupAdminPassword.value,
      passwordConfirmation: setupAdminPasswordConfirmation.value,
    };
  }

  async function finishMinimumLoading(startedAt, minimumMs) {
    const elapsed = performance.now() - startedAt;

    if (elapsed < minimumMs) {
      await wait(minimumMs - elapsed);
    }
  }

  function wait(ms) {
    return new Promise((resolve) => {
      window.setTimeout(resolve, ms);
    });
  }

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

  return {
    adminUrl,
    busy,
    canGoBack,
    canGoNext,
    clearStepErrors,
    clearStepFieldError,
    csrfToken,
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
  };
}
