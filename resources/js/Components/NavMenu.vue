<script setup lang="ts">
import { ChevronsRight, Pause, Play } from "lucide-vue-next";
import { NAV_ITEMS } from "@/config/navigation";
import { useNavMenuAnimation } from "@/composables/useNavMenuAnimation";

defineProps<{
  isPaused: boolean;
}>();

const emit = defineEmits<{
  (e: "togglePause"): void;
}>();

const { menuOpen, menuVisible, boxPhases, toggle } = useNavMenuAnimation(
  NAV_ITEMS.length,
);
</script>

<template>
  <div class="absolute left-4 top-4 z-20" @touchend.stop>
    <!-- Top controls row: trigger + pause/play -->
    <div class="flex items-center gap-2">
      <!-- Nav trigger -->
      <button
        @click="toggle"
        class="flex size-10 cursor-pointer items-center justify-center rounded-full bg-black/40 text-white backdrop-blur-sm transition hover:bg-black/60"
        :aria-label="menuOpen ? 'Close navigation' : 'Open navigation'"
      >
        <ChevronsRight
          class="size-5 transition-transform duration-300"
          :class="{ 'rotate-180': menuOpen }"
        />
      </button>

      <!-- Pause/play -->
      <button
        @click="emit('togglePause')"
        class="flex size-10 cursor-pointer items-center justify-center rounded-full bg-black/40 text-white backdrop-blur-sm transition hover:bg-black/60"
        :aria-label="isPaused ? 'Play quote rotation' : 'Pause quote rotation'"
      >
        <Pause v-if="!isPaused" class="size-5" />
        <Play v-if="isPaused" class="size-5" />
      </button>
    </div>

    <!-- Menu boxes — crawl out below the trigger -->
    <div v-if="menuVisible" class="mt-2 flex flex-col gap-0.5">
      <button
        v-for="(item, index) in NAV_ITEMS"
        :key="index"
        class="nav-box relative"
        :class="{
          'nav-box--entering': boxPhases[index] === 'entering',
          'nav-box--visible': boxPhases[index] === 'visible',
          'nav-box--leaving': boxPhases[index] === 'leaving',
        }"
        :aria-label="item.label"
      >
        <!-- SVG border that draws itself -->
        <svg
          width="44"
          height="44"
          class="pointer-events-none absolute top-0 left-0"
        >
          <rect
            width="42"
            height="42"
            x="1"
            y="1"
            fill="none"
            stroke="rgba(255,255,255,0.85)"
            stroke-width="1.5"
            stroke-dasharray="168"
            class="border-rect"
          />
        </svg>

        <!-- Box background + icon -->
        <div
          class="flex size-11 items-center justify-center bg-black/40 backdrop-blur-sm"
        >
          <component :is="item.icon" class="box-icon size-5 text-white" />
        </div>
      </button>
    </div>
  </div>
</template>

<style scoped>
/* Base: off-screen to the left, invisible */
.nav-box {
  transform: translateX(-64px);
  opacity: 0;
}

/* SVG border starts fully hidden */
.border-rect {
  stroke-dashoffset: 168;
}

/* ===== ENTERING ===== */
.nav-box--entering {
  animation: boxSlideIn 300ms ease forwards;
}

.nav-box--entering .border-rect {
  animation: borderDraw 400ms ease forwards;
  animation-delay: 300ms;
}

.nav-box--entering .box-icon {
  opacity: 0;
  animation: iconAppear 200ms ease forwards;
  animation-delay: 700ms;
}

/* ===== VISIBLE (static — holds the final open state) ===== */
.nav-box--visible {
  transform: translateX(0);
  opacity: 1;
}

.nav-box--visible .border-rect {
  stroke-dashoffset: 0;
}

.nav-box--visible .box-icon {
  opacity: 1;
}

/* ===== LEAVING ===== */
/* fill-mode: both ensures 'from' keyframe applies immediately (during delay too) */
.nav-box--leaving .box-icon {
  animation: iconDisappear 150ms ease both;
}

.nav-box--leaving .border-rect {
  animation: borderErase 250ms ease both;
  animation-delay: 100ms;
}

.nav-box--leaving {
  animation: boxSlideOut 300ms ease both;
  animation-delay: 350ms;
}

/* ===== KEYFRAMES ===== */
@keyframes boxSlideIn {
  from {
    transform: translateX(-64px);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

@keyframes boxSlideOut {
  from {
    transform: translateX(0);
    opacity: 1;
  }
  to {
    transform: translateX(-64px);
    opacity: 0;
  }
}

@keyframes borderDraw {
  from {
    stroke-dashoffset: 168;
  }
  to {
    stroke-dashoffset: 0;
  }
}

@keyframes borderErase {
  from {
    stroke-dashoffset: 0;
  }
  to {
    stroke-dashoffset: 168;
  }
}

@keyframes iconAppear {
  from {
    opacity: 0;
    transform: scale(0.6);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

@keyframes iconDisappear {
  from {
    opacity: 1;
    transform: scale(1);
  }
  to {
    opacity: 0;
    transform: scale(0.5);
  }
}

/* Respect reduced motion preference */
@media (prefers-reduced-motion: reduce) {
  .nav-box--entering {
    animation: none;
    transform: translateX(0);
    opacity: 1;
  }

  .nav-box--entering .border-rect {
    animation: none;
    stroke-dashoffset: 0;
  }

  .nav-box--entering .box-icon {
    animation: none;
    opacity: 1;
  }

  .nav-box--leaving {
    animation: none;
    opacity: 0;
  }

  .nav-box--leaving .border-rect,
  .nav-box--leaving .box-icon {
    animation: none;
  }
}
</style>
