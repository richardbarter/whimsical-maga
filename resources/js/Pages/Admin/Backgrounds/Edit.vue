<script setup lang="ts">
import type { Background } from '@/types';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import FormField from '@/Components/ui/form-field/FormField.vue';
import { formatDate, formatFileSize } from '@/lib/utils';

const props = defineProps<{
    background: Background;
}>();

const form = useForm({
    _method: 'PUT',
    image: null as File | null,
    title: props.background.title ?? '',
    alt_text: props.background.alt_text ?? '',
    description: props.background.description ?? '',
    credit: props.background.credit ?? '',
    source_url: props.background.source_url ?? '',
});

const newImagePreview = ref<string | null>(null);

function onFileChange(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    form.image = file;
    if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            newImagePreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    } else {
        newImagePreview.value = null;
    }
}

function submit() {
    form.post(route('admin.backgrounds.update', props.background.id), {
        forceFormData: true,
    });
}
</script>

<template>
    <Head title="Edit Background" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Edit Background
                </h2>
                <Link
                    :href="route('admin.backgrounds.index')"
                    class="text-sm text-gray-600 hover:text-gray-900"
                >
                    &larr; Back to Backgrounds
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Image -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Image</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <Label>Current Image</Label>
                                <img
                                    :src="background.url"
                                    :alt="background.alt_text ?? ''"
                                    class="h-40 w-full rounded-md object-cover"
                                />
                                <p class="text-xs text-muted-foreground">Upload a new image below to replace this one.</p>
                            </div>

                            <FormField label="Replace Image" for="image" :error="form.errors.image">
                                <input
                                    id="image"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-primary file:px-4 file:py-2 file:text-sm file:font-medium file:text-primary-foreground hover:file:bg-primary/90"
                                    @change="onFileChange"
                                />
                                <p class="text-xs text-muted-foreground">JPG, PNG, or WebP. Max 10 MB.</p>
                            </FormField>

                            <div v-if="newImagePreview" class="space-y-1">
                                <Label>New Image Preview</Label>
                                <img
                                    :src="newImagePreview"
                                    alt="New image preview"
                                    class="h-40 w-full rounded-md object-cover"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Details -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Details</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <FormField label="Title" for="title" :error="form.errors.title">
                                <Input id="title" v-model="form.title" placeholder="A short name for this background" />
                            </FormField>

                            <FormField label="Alt Text" for="alt_text" :error="form.errors.alt_text">
                                <Input id="alt_text" v-model="form.alt_text" placeholder="Describe the image for accessibility" />
                            </FormField>

                            <FormField label="Description" for="description" :error="form.errors.description">
                                <Textarea id="description" v-model="form.description" placeholder="Optional notes about this background..." class="min-h-[80px]" />
                            </FormField>

                            <FormField label="Credit" for="credit" :error="form.errors.credit">
                                <Input id="credit" v-model="form.credit" placeholder="Photographer or source name" />
                            </FormField>

                            <FormField label="Source URL" for="source_url" :error="form.errors.source_url">
                                <Input id="source_url" v-model="form.source_url" type="url" placeholder="https://example.com/original-image" />
                            </FormField>
                        </CardContent>
                    </Card>

                    <!-- Submit -->
                    <div class="flex items-center justify-end gap-3">
                        <Link
                            :href="route('admin.backgrounds.index')"
                            class="text-sm text-gray-600 hover:text-gray-900"
                        >
                            Cancel
                        </Link>
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Saving...' : 'Update Background' }}
                        </Button>
                    </div>
                </form>

                <!-- Read-only metadata -->
                <div class="mt-6 rounded-lg border bg-muted/40 px-4 py-3 text-sm text-muted-foreground">
                    <dl class="grid grid-cols-3 gap-4">
                        <div>
                            <dt class="font-medium">Dimensions</dt>
                            <dd class="mt-0.5">{{ background.dimensions ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium">File Size</dt>
                            <dd class="mt-0.5">{{ formatFileSize(background.file_size) }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium">Added</dt>
                            <dd class="mt-0.5">{{ formatDate(background.created_at) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
