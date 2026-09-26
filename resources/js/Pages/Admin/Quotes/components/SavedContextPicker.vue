<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { useSavedContextSearch } from '@/composables/useSavedContextSearch';

const emit = defineEmits<{
    select: [body: string];
}>();

const open = ref(false);
const { query, results, isLoading, error, reset } = useSavedContextSearch();

function onOpen(): void {
    open.value = true;
    reset();
}

function selectContext(body: string): void {
    emit('select', body);
    open.value = false;
}
</script>

<template>
    <Button type="button" variant="outline" size="sm" @click="onOpen">
        Load context
    </Button>

    <Dialog v-model:open="open">
        <DialogContent class="max-w-2xl">
            <DialogHeader>
                <DialogTitle>Load Saved Context</DialogTitle>
            </DialogHeader>

            <div class="space-y-4">
                <div class="space-y-1">
                    <Input
                        v-model="query"
                        placeholder="Search by subject or tag — separate multiple terms with commas..."
                        aria-label="Search saved contexts"
                        class="w-full"
                    />
                    <p class="text-xs text-muted-foreground">e.g. "January 6th, Trump" matches contexts relevant to both terms</p>
                </div>

                <div class="max-h-80 overflow-y-auto rounded-md border" aria-live="polite">
                    <div v-if="isLoading" class="p-4 text-center text-sm text-muted-foreground">
                        Loading...
                    </div>

                    <div v-else-if="error" class="p-4 text-center text-sm text-destructive">
                        {{ error }}
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
