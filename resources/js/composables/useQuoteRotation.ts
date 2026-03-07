import type { Quote } from "@/types";
import { computed, onMounted, onUnmounted, ref } from "vue";

const QUOTE_INTERVAL_MS = 10_000;
const BACKGROUND_EVERY_N_QUOTES = 3;

export function useQuoteRotation(
  quotes: Quote[],
  onBackgroundAdvance: () => void,
) {
  const currentQuoteIndex = ref(0);
  const quoteChangeCount = ref(0);

  const currentQuote = computed(() => quotes[currentQuoteIndex.value] ?? null);

  let quoteTimer: ReturnType<typeof setInterval> | null = null;

  function advanceQuote(): void {
    currentQuoteIndex.value = (currentQuoteIndex.value + 1) % quotes.length;
    quoteChangeCount.value++;

    if (quoteChangeCount.value % BACKGROUND_EVERY_N_QUOTES === 0) {
      onBackgroundAdvance();
    }
  }

  onMounted(() => {
    if (quotes.length > 1) {
      quoteTimer = setInterval(advanceQuote, QUOTE_INTERVAL_MS);
    }
  });

  onUnmounted(() => {
    if (quoteTimer !== null) {
      clearInterval(quoteTimer);
    }
  });

  return { currentQuote, currentQuoteIndex };
}
