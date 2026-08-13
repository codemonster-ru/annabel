import { onBeforeUnmount, onMounted, ref } from 'vue';

export function useMediaQuery(query) {
  const matches = ref(false);
  let mediaQuery = null;

  function update(event) {
    matches.value = event.matches;
  }

  onMounted(() => {
    mediaQuery = window.matchMedia(query);
    matches.value = mediaQuery.matches;
    mediaQuery.addEventListener('change', update);
  });

  onBeforeUnmount(() => {
    mediaQuery?.removeEventListener('change', update);
  });

  return matches;
}
