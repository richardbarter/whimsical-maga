import type { PageProps } from "@/types";
import { usePage } from "@inertiajs/vue3";
import { onUnmounted, ref, watch } from "vue";

const FLASH_DURATION_MS = 4000;

/**
 * The session flash messages from the latest visit, auto-dismissed after a few seconds.
 */
export function useFlashMessages() {
  const page = usePage<PageProps>();
  const flash = ref<{ success?: string; error?: string }>({});
  let dismissTimer: ReturnType<typeof setTimeout> | null = null;

  function clearTimer(): void {
    if (dismissTimer) {
      clearTimeout(dismissTimer);
    }
    dismissTimer = null;
  }

  watch(
    () => page.props.flash,
    (newFlash) => {
      flash.value = { ...newFlash };
      clearTimer();

      if (newFlash.success || newFlash.error) {
        dismissTimer = setTimeout(() => {
          flash.value = {};
        }, FLASH_DURATION_MS);
      }
    },
    { immediate: true },
  );

  onUnmounted(clearTimer);

  return { flash };
}
