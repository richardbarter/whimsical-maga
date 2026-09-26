<script setup lang="ts">
import type { Tag, ComboboxItem, SavedContext } from '@/types';
import { ref } from 'vue';
import { isAxiosError } from 'axios';
import { Button } from '@/Components/ui/button';
import { FormField } from '@/Components/ui/form-field';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
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
    saved: [savedContext: SavedContext];
}>();

const open = ref(false);
const saving = ref(false);
const subject = ref('');
const body = ref('');
const selectedTags = ref<ComboboxItem[]>([]);
const errors = ref<Record<string, string>>({});

function onOpen(): void {
    open.value = true;
    subject.value = '';
    body.value = props.currentBody;
    selectedTags.value = [];
    errors.value = {};
}

async function save(): Promise<void> {
    saving.value = true;
    errors.value = {};

    try {
        const { data } = await window.axios.post<SavedContext>(route('admin.saved-contexts.store'), {
            subject: subject.value,
            body: body.value,
            tags: selectedTags.value,
        });

        open.value = false;
        emit('saved', data);
    } catch (error: unknown) {
        errors.value = validationErrors(error);
    } finally {
        saving.value = false;
    }
}

/** Laravel's 422 response lists messages per field; show the first for each. */
function validationErrors(error: unknown): Record<string, string> {
    if (isAxiosError(error) && error.response?.status === 422) {
        const fieldErrors = error.response.data.errors as Record<string, string[]>;

        return Object.fromEntries(Object.entries(fieldErrors).map(([field, messages]) => [field, messages[0]]));
    }

    return { general: 'Failed to save context. Please try again.' };
}
</script>

<template>
    <Button type="button" variant="outline" size="sm" @click="onOpen">
        Save context
    </Button>

    <Dialog v-model:open="open">
        <DialogContent class="max-w-xl">
            <DialogHeader>
                <DialogTitle>Save Context to Library</DialogTitle>
            </DialogHeader>

            <form id="save-context-form" class="space-y-4" @submit.prevent="save">
                <FormField label="Subject *" for="save-context-subject" :error="errors.subject">
                    <Input
                        id="save-context-subject"
                        v-model="subject"
                        placeholder="e.g. Iran Nuclear Deal History..."
                    />
                </FormField>

                <FormField label="Context Body *" for="save-context-body" :error="errors.body">
                    <Textarea
                        id="save-context-body"
                        v-model="body"
                        class="min-h-[120px]"
                    />
                </FormField>

                <FormField label="Tags" :error="errors.tags">
                    <ComboboxMultiSelect
                        v-model="selectedTags"
                        :options="tags"
                        placeholder="Select or create tags..."
                        search-placeholder="Search tags..."
                        new-item-placeholder="New tag name..."
                    />
                </FormField>

                <p v-if="errors.general" class="text-sm text-destructive">{{ errors.general }}</p>
            </form>

            <DialogFooter>
                <Button type="button" variant="outline" :disabled="saving" @click="open = false">Cancel</Button>
                <Button type="submit" form="save-context-form" :disabled="saving">
                    {{ saving ? 'Saving...' : 'Save to Library' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
