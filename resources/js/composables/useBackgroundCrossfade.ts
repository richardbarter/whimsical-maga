import type { Background } from "@/types";
import { computed, onMounted, ref } from "vue";

export function useBackgroundCrossfade(backgrounds: Background[]) {
  const currentBackgroundIndex = ref(0);
  const layers = ref([{ bg: "" }, { bg: "" }]);
  const activeLayer = ref(0);

  const currentBackground = computed(
    () => backgrounds[currentBackgroundIndex.value] ?? null,
  );

  function preloadImage(url: string): Promise<void> {
    return new Promise((resolve) => {
      const img = new Image();
      img.onload = () => resolve();
      img.onerror = () => resolve();
      img.src = url;
    });
  }

  function preloadNextBackground(fromIdx: number): void {
    if (backgrounds.length <= 1) return;
    const nextIdx = (fromIdx + 1) % backgrounds.length;
    const next = backgrounds[nextIdx];
    if (next) {
      preloadImage(next.url);
    }
  }

  async function transitionToBackground(newIdx: number): Promise<void> {
    if (!backgrounds.length) return;

    const newBg = backgrounds[newIdx];
    if (!newBg) return;

    const inactiveIdx = (1 - activeLayer.value) as 0 | 1;
    layers.value[inactiveIdx].bg = newBg.url;

    await preloadImage(newBg.url);

    currentBackgroundIndex.value = newIdx;
    activeLayer.value = inactiveIdx;

    preloadNextBackground(newIdx);
  }

  function transitionToNext(): void {
    const nextIdx = (currentBackgroundIndex.value + 1) % backgrounds.length;
    transitionToBackground(nextIdx);
  }

  onMounted(() => {
    if (backgrounds.length > 0) {
      layers.value[0].bg = backgrounds[0].url;
      preloadNextBackground(0);
    }
  });

  return { layers, activeLayer, currentBackground, transitionToNext };
}
