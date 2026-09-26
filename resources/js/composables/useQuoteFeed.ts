import type { QuoteFeed } from "@/types";
import { router } from "@inertiajs/vue3";
import { ref } from "vue";

/**
 * Fetches the next batch of home-page quotes. The server appends the batch to the
 * `quotes` prop (an Inertia merge prop), so the page's quote list simply grows.
 */
export function useQuoteFeed(feed: () => QuoteFeed) {
  const isLoading = ref(false);

  function loadMore(): void {
    const { seed, page, hasMore } = feed();

    if (!hasMore || isLoading.value) {
      return;
    }

    isLoading.value = true;

    router.reload({
      only: ["quotes", "quoteFeed"],
      data: { seed, page: page + 1 },
      preserveUrl: true,
      onFinish: () => {
        isLoading.value = false;
      },
    });
  }

  return { isLoading, loadMore };
}
