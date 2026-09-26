<script setup lang="ts">
import type { Tag, SavedContextFormData } from '@/types';
import { Link, useForm } from '@inertiajs/vue3';
import { ComboboxMultiSelect } from '@/Components/ui/combobox-multi-select';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { FormField } from '@/Components/ui/form-field';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';

const props = defineProps<{
    tags: Tag[];
    initialValues?: Partial<SavedContextFormData>;
    submitLabel: string;
    submitMethod: 'post' | 'put';
    submitRoute: string;
}>();

const form = useForm<SavedContextFormData>({
    subject: props.initialValues?.subject ?? '',
    body: props.initialValues?.body ?? '',
    tags: props.initialValues?.tags ?? [],
});

function submit() {
    if (props.submitMethod === 'post') {
        form.post(props.submitRoute);
    } else {
        form.put(props.submitRoute);
    }
}
</script>

<template>
    <form @submit.prevent="submit" class="space-y-6">
        <!-- Details -->
        <Card>
            <CardHeader>
                <CardTitle>Details</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <FormField label="Subject *" for="subject" :error="form.errors.subject">
                    <Input
                        id="subject"
                        v-model="form.subject"
                        placeholder="e.g. Iran Nuclear Deal History, January 6th Overview..."
                    />
                </FormField>

                <FormField label="Context Body *" for="body" :error="form.errors.body">
                    <Textarea
                        id="body"
                        v-model="form.body"
                        placeholder="Write the reusable context text here..."
                        class="min-h-[160px]"
                    />
                </FormField>
            </CardContent>
        </Card>

        <!-- Tags -->
        <Card>
            <CardHeader>
                <CardTitle>Tags</CardTitle>
            </CardHeader>
            <CardContent>
                <ComboboxMultiSelect
                    v-model="form.tags"
                    :options="tags"
                    placeholder="Select or create tags..."
                    search-placeholder="Search tags..."
                    new-item-placeholder="New tag name..."
                    :error="form.errors.tags"
                />
            </CardContent>
        </Card>

        <!-- Submit -->
        <div class="flex items-center justify-end gap-3">
            <Link
                :href="route('admin.saved-contexts.index')"
                class="text-sm text-muted-foreground hover:text-foreground"
            >
                Cancel
            </Link>
            <Button type="submit" :disabled="form.processing">
                {{ form.processing ? 'Saving...' : submitLabel }}
            </Button>
        </div>
    </form>
</template>
