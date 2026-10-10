import type { Quote } from "@/types";
import { useRemember } from "@inertiajs/vue3";
import { computed, onMounted, onUnmounted, reactive, ref, watch, type Ref } from "vue";

const QUOTE_INTERVAL_MS = 10_000;
const BACKGROUND_EVERY_N_QUOTES = 3;

/** Ask for the next batch while this many unseen quotes are still loaded. */
const LOAD_MORE_THRESHOLD = 5;

const REMEMBER_KEY = "Home/quoteRotation";

interface QuoteRotationOptions {
  onBackgroundAdvance: () => void;
  onRunningLow: () => void;
  /**
   * While true (e.g. the card is hovered or focused) the rotation waits. This is separate
   * from isPaused, so holding never changes the visitor's own pause/play choice.
   */
  isHeld?: Readonly<Ref<boolean>>;
}

/** Indexes of the quotes seen so far, and which one is showing. */
interface RotationHistory {
  indexes: number[];
  position: number;
}

/**
 * Plays through quotes in the order given — the server has already shuffled them — and
 * wraps back to the start after the last one. Quotes may be appended while it runs.
 * History lets the visitor step back through what they've seen, and it's kept in the
 * browser's history entry (Inertia's useRemember), so pressing Back after opening a quote
 * page returns to the same quote.
 */
export function useQuoteRotation(
  quotes: Ref<Quote[]>,
  { onBackgroundAdvance, onRunningLow, isHeld = ref(false) }: QuoteRotationOptions,
) {
  // Given a reactive object, useRemember returns a reactive one (its type says `Ref<T> | T`)
  const seen = useRemember(reactive<RotationHistory>({ indexes: [0], position: 0 }), REMEMBER_KEY) as RotationHistory;
  const quoteChangeCount = ref(0);
  const isPaused = ref(false);

  const currentQuoteIndex = computed(
    () => seen.indexes[seen.position] ?? 0,
  );

  const currentQuote = computed(
    () => quotes.value[currentQuoteIndex.value] ?? null,
  );

  const canGoBack = computed(() => seen.position > 0);

  let quoteTimer: ReturnType<typeof setInterval> | null = null;

  function indexAfter(index: number): number {
    return index + 1 < quotes.value.length ? index + 1 : 0;
  }

  /** A remembered history only fits the quote list it was made with; start over if it doesn't. */
  function discardHistoryThatDoesNotFit(): void {
    const { indexes, position } = seen;
    const fits = position < indexes.length && indexes.every((index) => index < quotes.value.length);

    if (!fits) {
      seen.indexes = [0];
      seen.position = 0;
    }
  }

  function requestMoreIfRunningLow(): void {
    const unseenAhead = quotes.value.length - 1 - currentQuoteIndex.value;

    if (unseenAhead <= LOAD_MORE_THRESHOLD) {
      onRunningLow();
    }
  }

  function step(): void {
    if (seen.position < seen.indexes.length - 1) {
      seen.position++;
    } else {
      seen.indexes.push(indexAfter(currentQuoteIndex.value));
      seen.position++;
    }

    quoteChangeCount.value++;

    if (quoteChangeCount.value % BACKGROUND_EVERY_N_QUOTES === 0) {
      onBackgroundAdvance();
    }

    requestMoreIfRunningLow();
  }

  function stopTimer(): void {
    if (quoteTimer !== null) {
      clearInterval(quoteTimer);
    }
    quoteTimer = null;
  }

  /** Run the timer only when not paused, not held and there's more than one quote; a restart gets a full interval. */
  function syncTimer(): void {
    const shouldRun = !isPaused.value && !isHeld.value && quotes.value.length > 1;

    if (!shouldRun) {
      stopTimer();
    } else if (quoteTimer === null) {
      quoteTimer = setInterval(step, QUOTE_INTERVAL_MS);
    }
  }

  function pauseRotation(): void {
    isPaused.value = true;
    syncTimer();
  }

  function goToNext(): void {
    pauseRotation();
    step();
  }

  function goToPrev(): void {
    if (seen.position === 0) {
      return;
    }

    pauseRotation();
    seen.position--;
  }

  function togglePause(): void {
    isPaused.value = !isPaused.value;
    syncTimer();
  }

  watch(isHeld, syncTimer);

  onMounted(() => {
    syncTimer();
    requestMoreIfRunningLow();
  });

  onUnmounted(stopTimer);

  // Before the first render, so a stale remembered position never shows an empty card
  discardHistoryThatDoesNotFit();

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
