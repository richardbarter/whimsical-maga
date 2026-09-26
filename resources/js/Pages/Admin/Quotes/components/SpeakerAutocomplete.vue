<script setup lang="ts">
import type { Speaker } from '@/types';
import { computed, ref, watch } from 'vue';
import { Input } from '@/Components/ui/input';

const props = defineProps<{
    speakers: Speaker[];
    inputId?: string;
}>();

const MAX_SUGGESTIONS = 8;
const BLUR_CLOSE_DELAY_MS = 150;

const model = defineModel<string>({ default: '' });

const open = ref(false);
const activeIndex = ref(-1);
const listboxId = `${props.inputId ?? 'speaker'}-suggestions`;

const suggestions = computed(() => {
    if (!model.value.trim()) return [];

    const q = model.value.toLowerCase();

    return props.speakers.filter(s =>
        s.name.toLowerCase().includes(q) ||
        s.aliases?.some(a => a.alias.toLowerCase().includes(q))
    ).slice(0, MAX_SUGGESTIONS);
});

const isListVisible = computed(() => open.value && suggestions.value.length > 0);

function optionId(index: number): string {
    return `${listboxId}-${index}`;
}

function select(speaker: Speaker): void {
    model.value = speaker.name;
    open.value = false;
    activeIndex.value = -1;
}

function moveActive(step: 1 | -1): void {
    const count = suggestions.value.length;
    open.value = true;
    activeIndex.value = activeIndex.value < 0 && step === -1
        ? count - 1
        : (activeIndex.value + step + count) % count;
}

function onKeydown(event: KeyboardEvent): void {
    if (!suggestions.value.length) return;

    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        event.preventDefault();
        moveActive(event.key === 'ArrowDown' ? 1 : -1);
    } else if (event.key === 'Enter' && isListVisible.value && activeIndex.value >= 0) {
        event.preventDefault();
        select(suggestions.value[activeIndex.value]);
    } else if (event.key === 'Escape') {
        open.value = false;
        activeIndex.value = -1;
    }
}

function onBlur(): void {
    // Delay so a mouse click on a suggestion lands before the list closes.
    setTimeout(() => { open.value = false; }, BLUR_CLOSE_DELAY_MS);
}

watch(suggestions, () => { activeIndex.value = -1; });
</script>

<template>
    <div class="relative">
        <Input
            :id="inputId"
            v-model="model"
            placeholder="Who said this?"
            autocomplete="off"
            role="combobox"
            aria-autocomplete="list"
            :aria-expanded="isListVisible"
            :aria-controls="listboxId"
            :aria-activedescendant="activeIndex >= 0 ? optionId(activeIndex) : undefined"
            @focus="open = true"
            @input="open = true"
            @blur="onBlur"
            @keydown="onKeydown"
        />

        <ul
            v-if="isListVisible"
            :id="listboxId"
            role="listbox"
            class="absolute z-50 mt-1 w-full rounded-md border bg-popover p-1 shadow-md"
        >
            <li
                v-for="(speaker, index) in suggestions"
                :id="optionId(index)"
                :key="speaker.id"
                role="option"
                :aria-selected="index === activeIndex"
                class="cursor-pointer rounded-sm px-3 py-2 text-sm"
                :class="index === activeIndex ? 'bg-accent text-accent-foreground' : 'hover:bg-accent hover:text-accent-foreground'"
                @mousedown.prevent="select(speaker)"
            >
                {{ speaker.name }}
            </li>
        </ul>
    </div>
</template>
