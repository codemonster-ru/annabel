<script setup>
import { computed, useId } from 'vue';

const props = defineProps({
  error: {
    type: String,
    default: '',
  },
});

const errorId = useId();
const describedBy = computed(() => (props.error ? errorId : undefined));
</script>

<template>
  <fieldset class="app-fieldset" :aria-describedby="describedBy">
    <slot :described-by="describedBy" :invalid="Boolean(error)" />
    <p v-if="error" :id="errorId" class="app-fieldset__error">{{ error }}</p>
  </fieldset>
</template>
