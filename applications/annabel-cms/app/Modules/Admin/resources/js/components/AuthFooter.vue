<script setup>
import { VfLink } from '@codemonster-ru/vueforge-core/link';

const currentYear = new Date().getFullYear();
const copyrightYears = currentYear > 2026 ? `2026-${currentYear}` : '2026';
</script>

<template>
  <div class="auth-footer">
    <span>
      &copy; {{ copyrightYears }}
      <VfLink href="https://codemonster.net" target="_blank" rel="noopener noreferrer" underline="none">
        Codemonster
      </VfLink>.
      All rights reserved.
    </span>
  </div>
</template>
