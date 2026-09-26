import type { Quote } from "@/types";
import { computed, onMounted, onUnmounted, ref, type Ref } from "vue";

const QUOTE_INTERVAL_MS = 10_000;
const BACKGROUND_EVERY_N_QUOTES = 3;

/** Ask for the next batch while this many unseen quotes are still loaded. */
const LOAD_MORE_THRESHOLD = 5;

interface QuoteRotationOptions {
  onBackgroundAdvance: () => void;
  onRunningLow: () => void;
}

/**
 * Plays through quotes in the order given — the server has already shuffled them — and
 * wraps back to the start after the last one. Quotes may be appended while it runs.
 * History lets the visitor step back through what they've seen.
 */
export function useQuoteRotation(
  quotes: Ref<Quote[]>,
  { onBackgroundAdvance, onRunningLow }: QuoteRotationOptions,
) {
  const history = ref<number[]>([0]);
  const historyPosition = ref(0);
  const quoteChangeCount = ref(0);
  const isPaused = ref(false);

  const currentQuoteIndex = computed(
    () => history.value[historyPosition.value] ?? 0,
  );

  const currentQuote = computed(
    () => quotes.value[currentQuoteIndex.value] ?? null,
  );

  const canGoBack = computed(() => historyPosition.value > 0);

  let quoteTimer: ReturnType<typeof setInterval> | null = null;

  function indexAfter(index: number): number {
    return index + 1 < quotes.value.length ? index + 1 : 0;
  }

  function requestMoreIfRunningLow(): void {
    const unseenAhead = quotes.value.length - 1 - currentQuoteIndex.value;

    if (unseenAhead <= LOAD_MORE_THRESHOLD) {
      onRunningLow();
    }
  }

  function step(): void {
    if (historyPosition.value < history.value.length - 1) {
      historyPosition.value++;
    } else {
      history.value.push(indexAfter(currentQuoteIndex.value));
      historyPosition.value++;
    }

    quoteChangeCount.value++;

    if (quoteChangeCount.value % BACKGROUND_EVERY_N_QUOTES === 0) {
      onBackgroundAdvance();
    }

    requestMoreIfRunningLow();
  }

  function startTimer(): void {
    quoteTimer = setInterval(step, QUOTE_INTERVAL_MS);
  }

  function stopTimer(): void {
    if (quoteTimer !== null) {
      clearInterval(quoteTimer);
    }
    quoteTimer = null;
  }

  function pauseRotation(): void {
    if (!isPaused.value) {
      stopTimer();
      isPaused.value = true;
    }
  }

  function goToNext(): void {
    pauseRotation();
    step();
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
      startTimer();
      isPaused.value = false;
    } else {
      pauseRotation();
    }
  }

  onMounted(() => {
    if (quotes.value.length > 1) {
      startTimer();
    }

    requestMoreIfRunningLow();
  });

  onUnmounted(stopTimer);

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
