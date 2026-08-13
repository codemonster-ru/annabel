<script setup>
import { computed, ref, useId } from 'vue';

defineOptions({ inheritAttrs: false });

const props = defineProps({
  as: {
    type: String,
    default: 'div',
  },
  fillViewport: {
    type: Boolean,
    default: true,
  },
  sidebarCollapsed: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:sidebarCollapsed']);
const mobileSidebarOpen = ref(false);
const sidebarEngaged = ref(false);
const sidebarId = `app-admin-sidebar-${useId()}`;
const isSidebarCompact = computed(() => props.sidebarCollapsed && !sidebarEngaged.value);
const mobileToggleAttrs = computed(() => ({
  'aria-controls': sidebarId,
  'aria-expanded': mobileSidebarOpen.value,
  'aria-label': mobileSidebarOpen.value ? 'Close navigation' : 'Open navigation',
}));

function updateSidebarCollapsed(value) {
  if (value !== props.sidebarCollapsed) {
    emit('update:sidebarCollapsed', value);
  }
}

function toggleSidebarCollapsed() {
  updateSidebarCollapsed(!props.sidebarCollapsed);
}

function toggleMobileSidebar() {
  mobileSidebarOpen.value = !mobileSidebarOpen.value;
}

function closeMobileSidebar() {
  mobileSidebarOpen.value = false;
}

function handleFocusOut(event) {
  if (!event.currentTarget.contains(event.relatedTarget)) {
    sidebarEngaged.value = false;
  }
}

function handleKeydown(event) {
  if (event.key === 'Escape' && mobileSidebarOpen.value && !event.defaultPrevented) {
    event.preventDefault();
    closeMobileSidebar();
  }
}
</script>

<template>
  <component
    :is="as"
    v-bind="$attrs"
    class="app-admin-layout"
    :class="{
      'app-admin-layout--fill-viewport': fillViewport,
      'app-admin-layout--sidebar-collapsed': sidebarCollapsed,
      'app-admin-layout--mobile-sidebar-open': mobileSidebarOpen,
    }"
    @keydown="handleKeydown"
  >
    <aside
      :id="sidebarId"
      class="app-admin-layout__aside"
      @mouseenter="sidebarEngaged = true"
      @mouseleave="sidebarEngaged = false"
      @focusin="sidebarEngaged = true"
      @focusout="handleFocusOut"
    >
      <div class="app-admin-layout__brand">
        <slot
          name="brand"
          :is-sidebar-collapsed="sidebarCollapsed"
          :is-sidebar-compact="isSidebarCompact"
          :toggle-sidebar-collapsed="toggleSidebarCollapsed"
        />
      </div>
      <div class="app-admin-layout__aside-content">
        <slot
          name="aside"
          :is-sidebar-collapsed="sidebarCollapsed"
          :is-sidebar-compact="isSidebarCompact"
          :toggle-sidebar-collapsed="toggleSidebarCollapsed"
        />
      </div>
    </aside>

    <button
      class="app-admin-layout__mobile-backdrop"
      type="button"
      aria-label="Close navigation"
      tabindex="-1"
      @click="closeMobileSidebar"
    />

    <div class="app-admin-layout__main">
      <header class="app-admin-layout__header">
        <div class="app-admin-layout__mobile-toggle">
          <slot
            name="mobile-toggle"
            :mobile-toggle-attrs="mobileToggleAttrs"
            :toggle-mobile-sidebar="toggleMobileSidebar"
          >
            <button
              class="app-admin-layout__mobile-toggle-button"
              type="button"
              v-bind="mobileToggleAttrs"
              @click="toggleMobileSidebar"
            >
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>
          </slot>
        </div>
        <div class="app-admin-layout__mobile-brand">
          <slot name="mobile-brand" />
        </div>
        <div class="app-admin-layout__header-content">
          <slot
            name="header"
            :is-sidebar-collapsed="sidebarCollapsed"
            :toggle-sidebar-collapsed="toggleSidebarCollapsed"
            :toggle-mobile-sidebar="toggleMobileSidebar"
          />
        </div>
      </header>

      <main class="app-admin-layout__content">
        <slot />
      </main>

      <footer v-if="$slots.footer" class="app-admin-layout__footer">
        <slot name="footer" />
      </footer>
    </div>
  </component>
</template>

<style scoped>
.app-admin-layout {
  --app-admin-sidebar-width: 18rem;
  --app-admin-sidebar-collapsed-width: 4.75rem;
  --app-admin-header-height: 4rem;
  --app-admin-layer-header: 20;

  min-inline-size: 20rem;
  container-name: app-admin-layout;
  container-type: inline-size;
  background: var(--cm-color-background-surface-subtle);
  color: var(--cm-color-text-primary);
}

.app-admin-layout__aside {
  position: fixed;
  inset-block: 0;
  inset-inline-start: 0;
  z-index: var(--app-admin-layer-header);
  display: grid;
  grid-template-rows: var(--app-admin-header-height) minmax(0, 1fr);
  inline-size: var(--app-admin-sidebar-width);
  overflow: hidden;
  border-inline-end: var(--cm-border-width) solid var(--cm-color-border-default);
  background: var(--cm-color-background-surface);
  transition: inline-size var(--cm-motion-duration-normal) var(--cm-motion-ease-standard);
}

.app-admin-layout__brand {
  display: flex;
  align-items: center;
  min-inline-size: 0;
  padding: var(--cm-space-3) var(--cm-space-4);
  border-block-end: var(--cm-border-width) solid var(--cm-color-border-default);
}

.app-admin-layout__aside-content {
  min-inline-size: 0;
  min-block-size: 0;
  overflow-y: auto;
  padding: var(--cm-space-4);
}

.app-admin-layout__main {
  display: flex;
  flex-direction: column;
  min-inline-size: 0;
  margin-inline-start: var(--app-admin-sidebar-width);
  transition: margin-inline-start var(--cm-motion-duration-normal) var(--cm-motion-ease-standard);
}

.app-admin-layout--fill-viewport .app-admin-layout__main {
  min-block-size: 100vh;
}

.app-admin-layout__header {
  position: fixed;
  inset-block-start: 0;
  inset-inline: 0 0;
  inset-inline-start: var(--app-admin-sidebar-width);
  z-index: calc(var(--app-admin-layer-header) + 1);
  display: flex;
  align-items: center;
  block-size: var(--app-admin-header-height);
  padding: var(--cm-space-3) var(--cm-space-4);
  border-block-end: var(--cm-border-width) solid var(--cm-color-border-default);
  background: var(--cm-color-background-surface);
  transition: inset-inline-start var(--cm-motion-duration-normal) var(--cm-motion-ease-standard);
}

.app-admin-layout__header-content {
  display: flex;
  align-items: center;
  inline-size: 100%;
  min-inline-size: 0;
}

.app-admin-layout__mobile-toggle,
.app-admin-layout__mobile-brand,
.app-admin-layout__mobile-backdrop {
  display: none;
}

.app-admin-layout__content {
  flex: 1 0 auto;
  min-inline-size: 0;
  padding: calc(var(--app-admin-header-height) + var(--cm-space-4)) var(--cm-space-4) var(--cm-space-4);
  background: var(--cm-color-background-surface-subtle);
}

.app-admin-layout__footer {
  margin-block-start: auto;
  padding: var(--cm-space-4);
  border-block-start: var(--cm-border-width) solid var(--cm-color-border-default);
  background: var(--cm-color-background-surface);
}

.app-admin-layout--sidebar-collapsed .app-admin-layout__aside {
  inline-size: var(--app-admin-sidebar-collapsed-width);
}

.app-admin-layout--sidebar-collapsed .app-admin-layout__main {
  margin-inline-start: var(--app-admin-sidebar-collapsed-width);
}

.app-admin-layout--sidebar-collapsed .app-admin-layout__header {
  inset-inline-start: var(--app-admin-sidebar-collapsed-width);
}

.app-admin-layout--sidebar-collapsed .app-admin-layout__aside:is(:hover, :has(:focus-visible)) {
  z-index: calc(var(--app-admin-layer-header) + 2);
  inline-size: var(--app-admin-sidebar-width);
}

@container app-admin-layout (max-width: 1023.98px) {
  .app-admin-layout__aside,
  .app-admin-layout--sidebar-collapsed .app-admin-layout__aside {
    z-index: calc(var(--app-admin-layer-header) + 2);
    inline-size: min(var(--app-admin-sidebar-width), 85cqi);
    visibility: hidden;
    transform: translateX(-100%);
    transition:
      transform var(--cm-motion-duration-normal) var(--cm-motion-ease-standard),
      visibility 0s var(--cm-motion-duration-normal);
  }

  :global([dir='rtl']) .app-admin-layout__aside {
    transform: translateX(100%);
  }

  .app-admin-layout--mobile-sidebar-open .app-admin-layout__aside {
    visibility: visible;
    transform: translateX(0);
    transition:
      transform var(--cm-motion-duration-normal) var(--cm-motion-ease-standard),
      visibility 0s;
  }

  .app-admin-layout__main,
  .app-admin-layout--sidebar-collapsed .app-admin-layout__main {
    margin-inline-start: 0;
  }

  .app-admin-layout__header,
  .app-admin-layout--sidebar-collapsed .app-admin-layout__header {
    inset-inline-start: 0;
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
  }

  .app-admin-layout__mobile-toggle {
    display: flex;
    justify-self: start;
  }

  .app-admin-layout__mobile-toggle-button {
    display: inline-grid;
    place-items: center;
    inline-size: 2.5rem;
    block-size: 2.5rem;
    padding: 0;
    border: 0;
    border-radius: var(--cm-radius-control);
    background: transparent;
    color: inherit;
    cursor: pointer;
  }

  .app-admin-layout__mobile-toggle-button:hover {
    background: var(--cm-color-background-surface-hover);
  }

  .app-admin-layout__mobile-toggle-button:focus-visible {
    outline: 2px solid var(--cm-color-border-interactive);
    outline-offset: 2px;
  }

  .app-admin-layout__mobile-toggle-button svg {
    inline-size: 1.25rem;
    block-size: 1.25rem;
    fill: none;
    stroke: currentColor;
    stroke-linecap: round;
    stroke-width: 2;
  }

  .app-admin-layout__mobile-brand {
    display: flex;
    align-items: center;
    justify-content: center;
    min-inline-size: 0;
    grid-column: 2;
  }

  .app-admin-layout__header-content {
    inline-size: auto;
    justify-self: end;
    grid-column: 3;
  }

  .app-admin-layout__mobile-backdrop {
    position: fixed;
    inset: 0;
    z-index: calc(var(--app-admin-layer-header) + 1);
    display: block;
    inline-size: 100%;
    block-size: 100%;
    padding: 0;
    border: 0;
    background: rgb(0 0 0 / 45%);
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition:
      opacity var(--cm-motion-duration-normal) var(--cm-motion-ease-standard),
      visibility 0s var(--cm-motion-duration-normal);
  }

  .app-admin-layout--mobile-sidebar-open .app-admin-layout__mobile-backdrop {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
    transition:
      opacity var(--cm-motion-duration-normal) var(--cm-motion-ease-standard),
      visibility 0s;
  }

  .app-admin-layout--sidebar-collapsed .app-admin-layout__aside:is(:hover, :has(:focus-visible)) {
    inline-size: min(var(--app-admin-sidebar-width), 85cqi);
  }
}

@media (prefers-reduced-motion: reduce) {
  .app-admin-layout__aside,
  .app-admin-layout__main,
  .app-admin-layout__header,
  .app-admin-layout__mobile-backdrop {
    transition: none;
  }
}
</style>
