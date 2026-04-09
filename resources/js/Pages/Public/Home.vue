<script setup lang="ts">
import NavMenu from "@/Components/NavMenu.vue";
import PublicLayout from "@/Layouts/PublicLayout.vue";
import { useBackgroundCrossfade } from "@/composables/useBackgroundCrossfade";
import { useQuoteRotation } from "@/composables/useQuoteRotation";
import type { Background, Quote } from "@/types";
import { formatDate } from "@/lib/utils";
import { Head } from "@inertiajs/vue3";
import { ChevronLeft, ChevronRight } from "lucide-vue-next";
import { useSwipe } from "@vueuse/core";
import { onMounted, onUnmounted, ref } from "vue";

const props = defineProps<{
  quotes: Quote[];
  backgrounds: Background[];
}>();

const { layers, activeLayer, currentBackground, transitionToNext } =
  useBackgroundCrossfade(props.backgrounds);

const {
  currentQuote,
  currentQuoteIndex,
  isPaused,
  togglePause,
  goToNext,
  goToPrev,
  canGoBack,
} = useQuoteRotation(props.quotes, transitionToNext);

const container = ref<HTMLElement | null>(null);
const cardRef = ref<HTMLElement | null>(null);
let savedHeight = 0;
let heightAnimation: Animation | null = null;

function onBeforeLeave(): void {
  const card = cardRef.value;
  if (!card) return;

  // Read offsetHeight while any previous animation fill is still active,
  // so we capture the correct current visual height before cancelling it.
  savedHeight = card.offsetHeight; // ex: card is currently 300px

  // Cancel previous animation — its fill: forwards would otherwise override
  // the inline style we set below, causing scrollHeight to read stale values.
  heightAnimation?.cancel(); // ex: cancel any previous WAAPI animation
  heightAnimation = null;

  card.style.height = `${savedHeight}px`; // lock it to this height? if we let as auto, it would instantly snap to the new quote's height the moment Vue swaps the content.
  card.style.overflow = "hidden"; // clip anything that pokes out. technically shouldn't be a problem since quote text already fades out before this occurs, but just in case.
}

function onEnter(): void {
  const card = cardRef.value;
  if (!card) return;

  // Release height lock temporarily to measure new content's natural height.
  // Without this, scrollHeight returns savedHeight when shrinking (locked height wins).
  card.style.height = "auto";
  const newHeight = card.scrollHeight; // get what the new height would be
  card.style.height = `${savedHeight}px`; // set back to saved height before has a chance to render the auto height
  void card.offsetHeight; // force reflow so browser registers the restored height

  heightAnimation = card.animate(
    [{ height: `${savedHeight}px` }, { height: `${newHeight}px` }],
    { duration: 300, easing: "ease", fill: "forwards" },
  );
  heightAnimation.onfinish = () => {
    // commitStyles() bakes the final animated height into the inline style,
    // then cancel() removes the fill — without this the fill keeps overriding
    // inline styles and we lose the ability to measure correctly next time.
    heightAnimation?.commitStyles();
    heightAnimation?.cancel();
    card.style.height = "auto";
    card.style.overflow = "";
    heightAnimation = null;
  };
}

useSwipe(container, {
  onSwipeEnd(_e, direction) {
    if (direction === "left") goToNext();
    if (direction === "right") goToPrev();
  },
});

function onKeyDown(e: KeyboardEvent): void {
  if (e.key === "ArrowLeft") goToPrev();
  if (e.key === "ArrowRight") goToNext();
  if (e.key === " ") togglePause();
}

let lastTap = 0;

function onTouchEnd(): void {
  const now = Date.now();
  if (now - lastTap < 300) togglePause();
  lastTap = now;
}

onMounted(() => window.addEventListener("keydown", onKeyDown));
onUnmounted(() => window.removeEventListener("keydown", onKeyDown));
</script>

<template>
  <Head title="Home" />

  <PublicLayout>
    <div
      ref="container"
      class="relative flex min-h-screen items-center justify-center overflow-hidden bg-slate-900"
      @touchend="onTouchEnd"
    >
      <!-- Background layer 0 -->
      <div
        class="absolute inset-0 bg-cover bg-center transition-opacity duration-1500"
        :class="activeLayer === 0 ? 'opacity-100' : 'opacity-0'"
        :style="layers[0].bg ? `background-image: url('${layers[0].bg}')` : ''"
      />
      <!-- Background layer 1 -->
      <div
        class="absolute inset-0 bg-cover bg-center transition-opacity duration-1500"
        :class="activeLayer === 1 ? 'opacity-100' : 'opacity-0'"
        :style="layers[1].bg ? `background-image: url('${layers[1].bg}')` : ''"
      />

      <!-- Nav menu + pause/play -->
      <NavMenu :is-paused="isPaused" @toggle-pause="togglePause" />

      <!-- Previous quote button -->
      <button
        @click="goToPrev"
        :disabled="!canGoBack"
        class="absolute left-4 top-1/2 z-10 -translate-y-1/2 flex cursor-pointer items-center justify-center rounded-full bg-black/40 p-2.5 text-white backdrop-blur-sm transition hover:bg-black/60 disabled:cursor-not-allowed disabled:opacity-30"
        aria-label="Previous quote"
      >
        <ChevronLeft class="size-5" />
      </button>

      <!-- Next quote button -->
      <button
        @click="goToNext"
        class="absolute right-4 top-1/2 z-10 -translate-y-1/2 flex cursor-pointer items-center justify-center rounded-full bg-black/40 p-2.5 text-white backdrop-blur-sm transition hover:bg-black/60"
        aria-label="Next quote"
      >
        <ChevronRight class="size-5" />
      </button>

      <!-- Overlay for text readability -->
      <div class="absolute inset-0 bg-black/30" />

      <!-- Quote card -->
      <div class="relative z-10 mx-4 w-full max-w-3xl mt-20 mb-6">
        <div
          ref="cardRef"
          class="rounded-2xl bg-white/90 p-8 shadow-2xl backdrop-blur-sm sm:p-12"
        >
          <Transition
            name="quote-fade"
            mode="out-in"
            @before-leave="onBeforeLeave"
            @enter="onEnter"
          >
            <blockquote
              v-if="currentQuote"
              :key="currentQuoteIndex"
              class="text-center"
            >
              <p
                class="text-2xl font-medium leading-relaxed text-gray-800 sm:text-3xl"
              >
                "{{ currentQuote.text }}"
              </p>
              <footer class="mt-6 space-y-1">
                <p
                  v-if="currentQuote.speaker"
                  class="font-medium text-gray-700"
                >
                  — {{ currentQuote.speaker.name
                  }}<span
                    v-if="currentQuote.occurred_at"
                    class="font-normal text-gray-500"
                    >, {{ formatDate(currentQuote.occurred_at) }}</span
                  >
                </p>
                <p v-if="currentQuote.context" class="text-sm text-gray-600">
                  {{ currentQuote.context }}
                </p>
              </footer>
            </blockquote>

            <div v-else key="empty" class="text-center text-gray-600">
              <p class="text-xl">Welcome to Whimsical MAGA Quotes</p>
              <p class="mt-2 text-sm">
                No quotes available yet. Check back soon!
              </p>
            </div>
          </Transition>
        </div>
      </div>

      <!-- Background credit -->
      <p
        v-if="currentBackground?.credit"
        class="absolute bottom-4 right-4 z-10 text-xs text-white/60"
      >
        {{ currentBackground.credit }}
      </p>
    </div>
  </PublicLayout>
</template>

<style scoped>
/* Old quote fades out quickly */
.quote-fade-leave-active {
  transition: opacity 200ms ease;
}
.quote-fade-leave-to {
  opacity: 0;
}

/* New quote fades in after the card resize completes (300ms delay) */
.quote-fade-enter-active {
  transition: opacity 350ms ease 300ms;
}

/* New quote invisible, waiting for resize to complete before showing */
.quote-fade-enter-from {
  opacity: 0;
}
</style>
