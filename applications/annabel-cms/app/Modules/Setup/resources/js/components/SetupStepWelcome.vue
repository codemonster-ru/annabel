<script setup>
import { icons, VueIconify } from '@codemonster-ru/vueforge-icons';
import { CmLink } from '@codemonster-ru/ui-vue';
</script>

<template>
  <div class="setup-step">
    <p class="setup-muted">
      Thank you for choosing Annabel. This wizard will check the current server, collect the
      required installation settings, and prepare the CMS for first use.
    </p>

    <p class="setup-muted">
      Before installation starts, Annabel will verify server requirements and filesystem access.
      Then you will configure the database connection and create the first administrator account.
    </p>

    <p class="setup-muted">
      After installation, you can sign in to the admin panel and start working with the site.
    </p>

    <p class="setup-muted">
      Project information is available in the
      <CmLink
        class="setup-inline-link"
        href="https://github.com/codemonster-ru/annabel"
        target="_blank"
        underline="hover"
      >
        <span>Annabel repository</span>
        <VueIconify class="setup-inline-link__icon" :icon="icons.externalLink" aria-hidden="true" />
      </CmLink>.
    </p>
  </div>
</template>
