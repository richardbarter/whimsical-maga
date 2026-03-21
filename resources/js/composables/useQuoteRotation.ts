import type { Quote } from "@/types";
import { computed, onMounted, onUnmounted, ref } from "vue";

const QUOTE_INTERVAL_MS = 10_000;
const BACKGROUND_EVERY_N_QUOTES = 3;

export function useQuoteRotation(
  quotes: Quote[],
  onBackgroundAdvance: () => void,
) {
  const history = ref<number[]>([0]);
  const historyPosition = ref(0);
  const quoteChangeCount = ref(0);
  const isPaused = ref(false);

  const currentQuoteIndex = computed(
    () => history.value[historyPosition.value] ?? 0,
  );

  const currentQuote = computed(() => quotes[currentQuoteIndex.value] ?? null);

  const canGoBack = computed(() => historyPosition.value > 0);

  let quoteTimer: ReturnType<typeof setInterval> | null = null;

  function pickRandomIndex(): number {
    if (quotes.length <= 1) {
      return 0;
    }

    let next: number;

    do {
      next = Math.floor(Math.random() * quotes.length);
    } while (next === currentQuoteIndex.value);

    return next;
  }

  function pauseRotation(): void {
    if (!isPaused.value) {
      if (quoteTimer !== null) {
        clearInterval(quoteTimer);
      }
      quoteTimer = null;
      isPaused.value = true;
    }
  }

  function advanceQuote(): void {
    history.value = history.value.slice(0, historyPosition.value + 1);
    history.value.push(pickRandomIndex());
    historyPosition.value++;
    quoteChangeCount.value++;

    if (quoteChangeCount.value % BACKGROUND_EVERY_N_QUOTES === 0) {
      onBackgroundAdvance();
    }
  }

  function goToNext(): void {
    pauseRotation();

    if (historyPosition.value < history.value.length - 1) {
      historyPosition.value++;
    } else {
      history.value.push(pickRandomIndex());
      historyPosition.value++;
    }

    quoteChangeCount.value++;

    if (quoteChangeCount.value % BACKGROUND_EVERY_N_QUOTES === 0) {
      onBackgroundAdvance();
    }
  }

  function goToPrev(): void {
    if (historyPosition.value === 0) {
      return;
    }

    pauseRotation();
    historyPosition.value--;
  }

  function togglePause(): void {
    if (isPaused.value) {
      quoteTimer = setInterval(advanceQuote, QUOTE_INTERVAL_MS);
      isPaused.value = false;
    } else {
      if (quoteTimer !== null) {
        clearInterval(quoteTimer);
      }
      quoteTimer = null;
      isPaused.value = true;
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

  return {
    currentQuote,
    currentQuoteIndex,
    isPaused,
    togglePause,
    goToNext,
    goToPrev,
    canGoBack,
  };
}
