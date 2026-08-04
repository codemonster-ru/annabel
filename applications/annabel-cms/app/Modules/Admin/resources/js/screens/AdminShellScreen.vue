<script setup>
import { computed, ref } from 'vue';
import { useBreakpoint } from '@codemonster-ru/vueforge-core/foundation';
import { VfAvatar } from '@codemonster-ru/vueforge-core/avatar';
import { VfButton } from '@codemonster-ru/vueforge-core/button';
import { VfDivider } from '@codemonster-ru/vueforge-core/divider';
import { VfDropdown } from '@codemonster-ru/vueforge-core/dropdown';
import { VfIconButton } from '@codemonster-ru/vueforge-core/icon-button';
import { VfMenu, VfMenuItem } from '@codemonster-ru/vueforge-core/menu';
import { VfNavMenu } from '@codemonster-ru/vueforge-core/nav-menu';
import { VfThemeSwitch } from '@codemonster-ru/vueforge-core/theme-switch';
import { icons, VueIconify } from '@codemonster-ru/vueforge-icons';
import { VfAdminLayout } from '@codemonster-ru/vueforge-layouts/admin-layout';
import brandLogoUrl from '../../images/codemonster-icon.svg';
import AuthFooter from '../components/AuthFooter.vue';
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
  pageTitle: {
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
const sidebarStorageKey = 'annabel-admin-sidebar-collapsed';
const sidebarCollapsed = ref(readSidebarCollapsed());
const isDesktopViewport = useBreakpoint('lg');
const avatarLabel = computed(() => props.user?.email?.trim().slice(0, 2).toUpperCase() || '?');
const activeNavigationPath = computed(() => findNavigationPath(props.navigation, props.navigationValue));
const pageHeading = computed(() => props.pageTitle || activeNavigationPath.value[activeNavigationPath.value.length - 1]?.label || 'Dashboard');

function readSidebarCollapsed() {
  try {
    return window.localStorage.getItem(sidebarStorageKey) === 'true';
  } catch {
    return false;
  }
}

function openSite() {
  window.open('/', '_blank', 'noopener,noreferrer');
}

function updateSidebarCollapsed(value) {
  sidebarCollapsed.value = value;

  try {
    window.localStorage.setItem(sidebarStorageKey, String(value));
  } catch {
    // The sidebar remains usable when browser storage is unavailable.
  }
}

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

</script>

<template>
  <VfAdminLayout
    class="admin-layout"
    :sidebar-collapsed="sidebarCollapsed"
    fill-viewport
    @update:sidebar-collapsed="updateSidebarCollapsed"
  >
    <template #brand="{ isSidebarCompact }">
      <div
        class="admin-layout__brand-row"
        :class="{ 'admin-layout__brand-row--compact': isSidebarCompact }"
      >
        <a class="admin-layout__brand-content" href="/admin" aria-label="Admin dashboard">
          <span class="admin-layout__brand-logo-column" aria-hidden="true">
            <img class="admin-layout__brand-logo" :src="brandLogoUrl" alt="" />
          </span>
          <span class="admin-layout__brand-title">Annabel</span>
        </a>
        <span
          class="admin-layout__brand-site-action"
          :aria-hidden="isSidebarCompact"
        >
          <VfButton
            variant="ghost"
            :tabindex="isSidebarCompact ? -1 : undefined"
            @click="openSite"
          >
            <VueIconify :icon="icons.externalLink" aria-hidden="true" />
            <span>View site</span>
          </VfButton>
        </span>
      </div>
    </template>

    <template #aside="{ isSidebarCompact }">
      <VfNavMenu
        :items="navigation"
        :model-value="navigationValue"
        :compact="isSidebarCompact"
        expand-mode="multiple"
        variant="sidebar"
        aria-label="Admin navigation"
      />
    </template>

    <template #mobile-brand>
      <a class="admin-layout__mobile-brand-content" href="/admin" aria-label="Admin dashboard">
        <img class="admin-layout__brand-logo" :src="brandLogoUrl" alt="" aria-hidden="true" />
        <span class="admin-layout__brand-title">Annabel</span>
      </a>
    </template>

    <template #header="{ toggleSidebarCollapsed }">
      <VfIconButton
        v-if="isDesktopViewport"
        class="admin-layout__sidebar-toggle"
        :icon="icons.bars"
        :aria-label="sidebarCollapsed ? 'Развернуть боковую панель' : 'Свернуть боковую панель'"
        :title="sidebarCollapsed ? 'Развернуть боковую панель' : 'Свернуть боковую панель'"
        :aria-pressed="sidebarCollapsed"
        @click="toggleSidebarCollapsed"
      />
      <div class="admin-layout__actions">
        <VfThemeSwitch v-if="isDesktopViewport" variant="button" button-variant="ghost" />
        <VfDivider v-if="isDesktopViewport" orientation="vertical" />
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
          <VfDivider v-if="!isDesktopViewport" />
          <div
            v-if="!isDesktopViewport"
            class="admin-user-menu__theme"
            @pointerdown.stop
            @click.stop
          >
            <span class="admin-user-menu__theme-label">Theme</span>
            <VfThemeSwitch variant="switch" />
          </div>
          <VfDivider />
          <VfMenu>
            <VfMenuItem label="Logout" :icon="icons.logOut" :disabled="loading" @select="logout" />
          </VfMenu>
        </VfDropdown>
      </div>
    </template>

    <div class="admin-layout__content">
      <div class="admin-layout__page-heading">
        <div class="admin-layout__page-heading-top">
          <h1>{{ pageHeading }}</h1>
          <div id="admin-page-actions" class="admin-layout__page-actions"></div>
        </div>
        <p v-if="error" class="field__error">{{ error }}</p>
      </div>
      <component :is="screenComponent" v-if="screenComponent" />
      <MissingAdminScreen v-else-if="screenError" :screen="screenError" />
    </div>

    <template #footer>
      <AuthFooter />
    </template>
  </VfAdminLayout>
</template>
