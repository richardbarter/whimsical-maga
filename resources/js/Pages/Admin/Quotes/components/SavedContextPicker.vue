<script setup lang="ts">
import type { SavedContext } from '@/types';
import { ref, watch } from 'vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';

const emit = defineEmits<{
    select: [body: string];
}>();

// State
const open = ref(false);
const searchQuery = ref('');
const results = ref<SavedContext[]>([]);
const loading = ref(false);

let debounceTimer: ReturnType<typeof setTimeout> | null = null;

async function fetchResults() {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (searchQuery.value) {
            params.set('q', searchQuery.value);
        }

        const response = await fetch(route('admin.saved-contexts.search') + '?' + params.toString(), {
            headers: { Accept: 'application/json' },
        });
        results.value = await response.json();
    } finally {
        loading.value = false;
    }
}

function onOpen() {
    open.value = true;
    searchQuery.value = '';
    fetchResults();
}

function selectContext(body: string) {
    emit('select', body);
    open.value = false;
}

watch(searchQuery, () => {
    if (debounceTimer) { clearTimeout(debounceTimer); }
    debounceTimer = setTimeout(fetchResults, 300);
});
</script>

<template>
    <Button type="button" variant="outline" size="sm" @click="onOpen">
        Load context
    </Button>

    <Dialog :open="open" @update:open="val => { open = val }">
        <DialogContent class="max-w-2xl">
            <DialogHeader>
                <DialogTitle>Load Saved Context</DialogTitle>
            </DialogHeader>

            <div class="space-y-4">
                <!-- Search input -->
                <div class="space-y-1">
                    <Input
                        v-model="searchQuery"
                        placeholder="Search by subject or tag — separate multiple terms with commas..."
                        class="w-full"
                    />
                    <p class="text-xs text-muted-foreground">e.g. "January 6th, Trump" matches contexts relevant to both terms</p>
                </div>

                <!-- Results list -->
                <div class="max-h-80 overflow-y-auto rounded-md border">
                    <div v-if="loading" class="p-4 text-center text-sm text-muted-foreground">
                        Loading...
                    </div>

                    <div v-else-if="!results.length" class="p-4 text-center text-sm text-muted-foreground">
                        No saved contexts found.
                    </div>

                    <button
                        v-for="result in results"
                        v-else
                        :key="result.id"
                        type="button"
                        class="w-full border-b px-4 py-3 text-left transition-colors last:border-b-0 hover:bg-muted"
                        @click="selectContext(result.body)"
                    >
                        <div class="font-medium text-sm">{{ result.subject }}</div>
                        <p class="mt-1 line-clamp-2 text-xs text-muted-foreground">{{ result.body }}</p>
                        <div v-if="result.tags?.length" class="mt-1.5 flex flex-wrap gap-1">
                            <Badge v-for="tag in result.tags" :key="tag.id" variant="secondary" class="text-xs">
                                {{ tag.name }}
                            </Badge>
                        </div>
                    </button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
