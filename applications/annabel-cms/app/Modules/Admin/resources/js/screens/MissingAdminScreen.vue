<script setup>
defineProps({
  screen: {
    type: String,
    required: true,
  },
});
</script>

<template>
  <p class="field__error">
    The admin screen "{{ screen }}" is not registered.
  </p>
</template>
