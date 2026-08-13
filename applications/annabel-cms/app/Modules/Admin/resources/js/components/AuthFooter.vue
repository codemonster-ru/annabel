<script setup>
import { CmLink } from '@codemonster-ru/ui-vue';

const currentYear = new Date().getFullYear();
const copyrightYears = currentYear > 2026 ? `2026-${currentYear}` : '2026';
</script>

<template>
  <div class="auth-footer">
    <span>
      &copy; {{ copyrightYears }}
      <CmLink href="https://codemonster.net" target="_blank" rel="noopener noreferrer" underline="none">
        Codemonster
      </CmLink>.
      All rights reserved.
    </span>
  </div>
</template>
