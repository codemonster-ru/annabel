<script setup>
import { computed, ref } from 'vue';
import { VfAvatar } from '@codemonster-ru/vueforge-core/avatar';
import { VfBreadcrumbs } from '@codemonster-ru/vueforge-core/breadcrumbs';
import { VfDivider } from '@codemonster-ru/vueforge-core/divider';
import { VfDropdown } from '@codemonster-ru/vueforge-core/dropdown';
import { VfIconButton } from '@codemonster-ru/vueforge-core/icon-button';
import { VfNavMenu } from '@codemonster-ru/vueforge-core/nav-menu';
import { VfThemeSwitch } from '@codemonster-ru/vueforge-core/theme-switch';
import { VfAdminLayout } from '@codemonster-ru/vueforge-layouts/admin-layout';
import brandLogoUrl from '../../images/codemonster-icon.svg';
import MissingAdminScreen from './MissingAdminScreen.vue';

const props = defineProps({
  csrfToken: {
    type: String,
    required: true,
  },
  navigation: {
    type: Array,
    default: () => [],
  },
  navigationValue: {
    type: String,
    default: '',
  },
  screenComponent: {
    type: [Object, Function],
    default: null,
  },
  screenError: {
    type: String,
    default: '',
  },
  user: {
    type: Object,
    default: null,
  },
});

const error = ref('');
const loading = ref(false);
const avatarLabel = computed(() => props.user?.email?.trim().slice(0, 2).toUpperCase() || '?');
const activeNavigationPath = computed(() => findNavigationPath(props.navigation, props.navigationValue));
const pageTitle = computed(() => activeNavigationPath.value[activeNavigationPath.value.length - 1]?.label || 'Dashboard');
const breadcrumbs = computed(() => activeNavigationPath.value.map((item, index, items) => ({
  label: item.label,
  href: index === items.length - 1 ? undefined : item.href,
  current: index === items.length - 1,
})));

function findNavigationPath(items, value, path = []) {
  for (const item of items) {
    const currentPath = [...path, item];

    if (item.value === value) {
      return currentPath;
    }

    const nestedPath = findNavigationPath(item.children || [], value, currentPath);

    if (nestedPath.length > 0) {
      return nestedPath;
    }
  }

  return [];
}

async function logout() {
  error.value = '';
  loading.value = true;

  const body = new FormData();
  body.append('_token', props.csrfToken);

  try {
    const response = await fetch('/admin/logout', {
      method: 'POST',
      headers: { Accept: 'application/json' },
      body,
      credentials: 'same-origin',
    });
    const payload = await response.json();

    if (!response.ok) {
      error.value = payload.message || 'Unable to sign out.';
      return;
    }

    window.location.assign('/admin/login');
  } catch (e) {
    error.value = 'Unable to sign out. Please try again.';
  } finally {
    loading.value = false;
  }
}

function goHome() {
  window.location.assign('/');
}
</script>

<template>
  <VfAdminLayout class="admin-layout">
    <template #brand>
      <div class="admin-layout__brand-content">
        <img class="admin-layout__brand-logo" :src="brandLogoUrl" alt="" />
        <span class="admin-layout__brand-title">Annabel CMS</span>
        <VfIconButton
          icon="house"
          variant="ghost"
          aria-label="Open CMS home page"
          title="Open CMS home page"
          @click="goHome"
        />
      </div>
    </template>

    <template #aside>
      <VfNavMenu
        :items="navigation"
        :model-value="navigationValue"
        expand-mode="multiple"
        variant="pills"
        aria-label="Admin navigation"
      />
    </template>

    <template #header>
      <div class="admin-layout__actions">
        <VfThemeSwitch variant="button" button-variant="ghost" />
        <VfDivider orientation="vertical" />
        <VfDropdown placement="bottom-end">
          <template #trigger>
            <VfAvatar
              :label="avatarLabel"
              shape="circle"
              :aria-label="user?.email || 'Current user'"
              :title="user?.email || 'Current user'"
            />
          </template>

          <div class="admin-user-menu">
            <span class="admin-user-menu__username">{{ user?.username || 'Current user' }}</span>
            <span class="admin-user-menu__email">{{ user?.email || '' }}</span>
          </div>
          <VfDivider />
          <button
            class="vf-dropdown__item"
            type="button"
            role="menuitem"
            :disabled="loading"
            @click="logout"
          >
            Logout
          </button>
        </VfDropdown>
      </div>
    </template>

    <div class="admin-layout__content">
      <div class="admin-layout__page-heading">
        <h1>{{ pageTitle }}</h1>
        <VfBreadcrumbs :items="breadcrumbs">
          <template #separator>/</template>
        </VfBreadcrumbs>
        <p v-if="error" class="field__error">{{ error }}</p>
      </div>
      <component :is="screenComponent" v-if="screenComponent" />
      <MissingAdminScreen v-else-if="screenError" :screen="screenError" />
    </div>
  </VfAdminLayout>
</template>
