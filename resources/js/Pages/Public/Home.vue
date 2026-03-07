<script setup lang="ts">
import PublicLayout from "@/Layouts/PublicLayout.vue";
import { useBackgroundCrossfade } from "@/composables/useBackgroundCrossfade";
import { useQuoteRotation } from "@/composables/useQuoteRotation";
import type { Background, Quote } from "@/types";
import { formatDate } from "@/lib/utils";
import { Head } from "@inertiajs/vue3";

const props = defineProps<{
  quotes: Quote[];
  backgrounds: Background[];
}>();

const { layers, activeLayer, currentBackground, transitionToNext } =
  useBackgroundCrossfade(props.backgrounds);

const { currentQuote, currentQuoteIndex } = useQuoteRotation(
  props.quotes,
  transitionToNext,
);
</script>

<template>
  <Head title="Home" />

  <PublicLayout>
    <div
      class="relative flex min-h-screen items-center justify-center overflow-hidden bg-slate-900"
    >
      <!-- Background layer 0 -->
      <div
        class="absolute inset-0 bg-cover bg-center transition-opacity duration-[1500ms]"
        :class="activeLayer === 0 ? 'opacity-100' : 'opacity-0'"
        :style="layers[0].bg ? `background-image: url('${layers[0].bg}')` : ''"
      />
      <!-- Background layer 1 -->
      <div
        class="absolute inset-0 bg-cover bg-center transition-opacity duration-[1500ms]"
        :class="activeLayer === 1 ? 'opacity-100' : 'opacity-0'"
        :style="layers[1].bg ? `background-image: url('${layers[1].bg}')` : ''"
      />

      <!-- Overlay for text readability -->
      <div class="absolute inset-0 bg-black/30" />

      <!-- Quote card -->
      <div class="relative z-10 mx-4 w-full max-w-3xl">
        <div
          class="rounded-2xl bg-white/90 p-8 shadow-2xl backdrop-blur-sm sm:p-12"
        >
          <Transition name="quote-fade" mode="out-in">
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
                  — {{ currentQuote.speaker.name }}
                </p>
                <p v-if="currentQuote.context" class="text-sm text-gray-600">
                  {{ currentQuote.context }}
                </p>
                <p
                  v-if="currentQuote.occurred_at"
                  class="text-sm text-gray-500"
                >
                  {{ formatDate(currentQuote.occurred_at) }}
                </p>
              </footer>
            </blockquote>

            <div v-else key="empty" class="text-center text-gray-600">
              <p class="text-xl">Welcome to Whimsical MAGA Quips</p>
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
.quote-fade-enter-active {
  transition: opacity 0.6s ease;
}

.quote-fade-leave-active {
  transition: opacity 0.4s ease;
}

.quote-fade-enter-from,
.quote-fade-leave-to {
  opacity: 0;
}
</style>
