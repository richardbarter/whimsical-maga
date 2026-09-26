<script setup lang="ts">
import type { Tag, ComboboxItem } from '@/types';
import { ref, watch } from 'vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Label } from '@/Components/ui/label';
import { ComboboxMultiSelect } from '@/Components/ui/combobox-multi-select';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';

const props = defineProps<{
    currentBody: string;
    tags: Tag[];
}>();

const emit = defineEmits<{
    saved: [];
}>();

// State
const open = ref(false);
const saving = ref(false);
const subject = ref('');
const body = ref('');
const selectedTags = ref<ComboboxItem[]>([]);
const error = ref<string | null>(null);

function onOpen() {
    open.value = true;
    subject.value = '';
    body.value = props.currentBody;
    selectedTags.value = [];
    error.value = null;
}

async function save() {
    if (!subject.value.trim()) {
        error.value = 'Subject is required.';
        return;
    }
    if (!body.value.trim()) {
        error.value = 'Body is required.';
        return;
    }

    saving.value = true;
    error.value = null;

    try {
        await window.axios.post(route('admin.saved-contexts.store'), {
            subject: subject.value,
            body: body.value,
            tags: selectedTags.value,
        });

        open.value = false;
        emit('saved');
    } catch (e: unknown) {
        const err = e as { response?: { data?: { message?: string } } };
        error.value = err.response?.data?.message ?? 'Failed to save context.';
    } finally {
        saving.value = false;
    }
}

// Keep body in sync with currentBody when dialog opens
watch(() => props.currentBody, (newVal) => {
    if (!open.value) { return; }
    body.value = newVal;
});
</script>

<template>
    <Button type="button" variant="outline" size="sm" @click="onOpen">
        Save context
    </Button>

    <Dialog :open="open" @update:open="val => { open = val }">
        <DialogContent class="max-w-xl">
            <DialogHeader>
                <DialogTitle>Save Context to Library</DialogTitle>
            </DialogHeader>

            <div class="space-y-4">
                <div class="space-y-2">
                    <Label for="save-context-subject">Subject *</Label>
                    <Input
                        id="save-context-subject"
                        v-model="subject"
                        placeholder="e.g. Iran Nuclear Deal History..."
                    />
                </div>

                <div class="space-y-2">
                    <Label for="save-context-body">Context Body *</Label>
                    <Textarea
                        id="save-context-body"
                        v-model="body"
                        class="min-h-[120px]"
                    />
                </div>

                <div class="space-y-2">
                    <Label>Tags</Label>
                    <ComboboxMultiSelect
                        v-model="selectedTags"
                        :options="tags"
                        placeholder="Select or create tags..."
                        search-placeholder="Search tags..."
                        new-item-placeholder="New tag name..."
                    />
                </div>

                <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
            </div>

            <DialogFooter>
                <Button variant="outline" :disabled="saving" @click="open = false">Cancel</Button>
                <Button :disabled="saving" @click="save">
                    {{ saving ? 'Saving...' : 'Save to Library' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
