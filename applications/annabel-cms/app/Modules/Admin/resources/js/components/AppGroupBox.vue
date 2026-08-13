<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
});
</script>

<template>
  <fieldset class="app-group-box">
    <legend class="app-group-box__title">{{ title }}</legend>
    <slot />
  </fieldset>
</template>
