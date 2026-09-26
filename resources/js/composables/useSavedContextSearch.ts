import type { SavedContext } from "@/types";
import { isCancel } from "axios";
import { onUnmounted, ref, watch } from "vue";

const DEBOUNCE_MS = 300;

/**
 * Debounced search against the saved-context library. A new search cancels the one in
 * flight, so a slow earlier response can never overwrite newer results.
 */
export function useSavedContextSearch() {
  const query = ref("");
  const results = ref<SavedContext[]>([]);
  const isLoading = ref(false);
  const error = ref<string | null>(null);

  let debounceTimer: ReturnType<typeof setTimeout> | null = null;
  let inFlight: AbortController | null = null;

  function clearDebounce(): void {
    if (debounceTimer) {
      clearTimeout(debounceTimer);
    }
    debounceTimer = null;
  }

  async function search(): Promise<void> {
    clearDebounce();
    inFlight?.abort();
    inFlight = new AbortController();

    isLoading.value = true;
    error.value = null;

    try {
      const { data } = await window.axios.get<SavedContext[]>(route("admin.saved-contexts.search"), {
        params: query.value ? { q: query.value } : {},
        signal: inFlight.signal,
      });
      results.value = data;
      isLoading.value = false;
    } catch (caught: unknown) {
      if (isCancel(caught)) {
        return;
      }
      results.value = [];
      error.value = "Couldn't load saved contexts. Please try again.";
      isLoading.value = false;
    }
  }

  /** Reset the query and load the unfiltered list straight away. */
  function reset(): void {
    query.value = "";
    search();
  }

  // Synchronous so reset()'s immediate search can cancel the debounce its query change schedules.
  watch(query, () => {
    clearDebounce();
    debounceTimer = setTimeout(search, DEBOUNCE_MS);
  }, { flush: "sync" });

  onUnmounted(() => {
    clearDebounce();
    inFlight?.abort();
  });

  return { query, results, isLoading, error, reset };
}
