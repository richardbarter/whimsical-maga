import { onUnmounted, ref } from "vue";

export type BoxPhase = "idle" | "entering" | "visible" | "leaving";

// Timing constants (ms) — must match CSS animation durations/delays in NavMenu.vue
// Open sequence per item:
//   box slide:   0–300ms
//   border draw: 300–700ms (delay 300ms, duration 400ms)
//   icon appear: 700–900ms (delay 700ms, duration 200ms)
const STAGGER_MS = 150;
const ITEM_ENTER_DURATION_MS = 900;

// Close sequence per item:
//   icon out:      0–150ms
//   border erase:  100–350ms (delay 100ms, duration 250ms)
//   box slide out: 350–650ms (delay 350ms, duration 300ms)
const ITEM_LEAVE_DURATION_MS = 650;

export function useNavMenuAnimation(numItems: number) {
    const TOTAL_CLOSE_MS =
        (numItems - 1) * STAGGER_MS + ITEM_LEAVE_DURATION_MS + 100;

    const menuOpen = ref(false);
    const menuVisible = ref(false);
    const boxPhases = ref<BoxPhase[]>(Array(numItems).fill("idle"));

    // Each toggle increments this. Callbacks check their captured gen against
    // the current value — if they differ, a newer toggle fired and they bail out.
    let currentGen = 0;

    function openMenu(): void {
        const gen = ++currentGen;
        boxPhases.value = Array(numItems).fill("idle");
        menuVisible.value = true;
        menuOpen.value = true;

        for (let i = 0; i < numItems; i++) {
            setTimeout(() => {
                if (currentGen !== gen) return;
                boxPhases.value[i] = "entering";

                setTimeout(() => {
                    if (currentGen !== gen) return;
                    if (boxPhases.value[i] === "entering") {
                        boxPhases.value[i] = "visible";
                    }
                }, ITEM_ENTER_DURATION_MS);
            }, i * STAGGER_MS);
        }
    }

    function closeMenu(): void {
        const gen = ++currentGen;
        menuOpen.value = false;
        boxPhases.value = Array(numItems).fill("visible");

        for (let ri = 0; ri < numItems; ri++) {
            const i = numItems - 1 - ri;
            setTimeout(() => {
                if (currentGen !== gen) return;
                boxPhases.value[i] = "leaving";
            }, ri * STAGGER_MS);
        }

        setTimeout(() => {
            if (currentGen !== gen) return;
            boxPhases.value = Array(numItems).fill("idle");
            menuVisible.value = false;
        }, TOTAL_CLOSE_MS);
    }

    function toggle(): void {
        if (menuOpen.value) {
            closeMenu();
        } else {
            openMenu();
        }
    }

    // Invalidate any pending callbacks when the component unmounts mid-animation
    onUnmounted(() => {
        currentGen++;
    });

    return { menuOpen, menuVisible, boxPhases, toggle };
}
