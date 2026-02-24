<script setup lang="ts">
import type { Speaker } from '@/types';
import { ref, computed } from 'vue';
import { Input } from '@/Components/ui/input';

const props = defineProps<{
    speakers: Speaker[];
    error?: string;
}>();

const model = defineModel<string>({ default: '' });

const open = ref(false);

const suggestions = computed(() => {
    if (!model.value.trim()) return [];

    const q = model.value.toLowerCase();

    return props.speakers.filter(s =>
        s.name.toLowerCase().includes(q) ||
        s.aliases?.some(a => a.alias.toLowerCase().includes(q))
    ).slice(0, 8);
});

function select(speaker: Speaker) {
    model.value = speaker.name;
    open.value = false;
}

function onBlur() {
    setTimeout(() => { open.value = false; }, 150);
}
</script>

<template>
    <div class="relative">
        <Input
            v-model="model"
            placeholder="Who said this?"
            autocomplete="off"
            @focus="open = true"
            @blur="onBlur"
        />

        <div
            v-if="open && suggestions.length > 0"
            class="absolute z-50 mt-1 w-full rounded-md border bg-popover shadow-md"
        >
            <button
                v-for="speaker in suggestions"
                :key="speaker.id"
                type="button"
                class="w-full px-3 py-2 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                @mousedown.prevent="select(speaker)"
            >
                {{ speaker.name }}
            </button>
        </div>

        <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
    </div>
</template>
