<script setup lang="ts">
import type { BackgroundFormData } from '@/types';
import { Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { FormField } from '@/Components/ui/form-field';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { useImagePreview } from '@/composables/useImagePreview';

const props = defineProps<{
    initialValues?: Partial<BackgroundFormData>;
    /** Set when editing: the image currently in use. */
    currentImage?: { url: string; alt: string };
    submitLabel: string;
    submitMethod: 'post' | 'put';
    submitRoute: string;
}>();

const form = useForm<BackgroundFormData>({
    image: null,
    title: props.initialValues?.title ?? '',
    alt_text: props.initialValues?.alt_text ?? '',
    description: props.initialValues?.description ?? '',
    credit: props.initialValues?.credit ?? '',
    source_url: props.initialValues?.source_url ?? '',
});

const { previewUrl, setFile } = useImagePreview();

function onFileChange(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    form.image = file;
    setFile(file);
}

function submit(): void {
    // File uploads must be multipart POSTs; updates spoof PUT via _method.
    form
        .transform((data) => (props.submitMethod === 'put' ? { ...data, _method: 'PUT' } : data))
        .post(props.submitRoute, { forceFormData: true });
}
</script>

<template>
    <form @submit.prevent="submit" class="space-y-6">
        <Card>
            <CardHeader>
                <CardTitle>Image</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div v-if="currentImage" class="space-y-2">
                    <Label>Current Image</Label>
                    <img :src="currentImage.url" :alt="currentImage.alt" class="h-40 w-full rounded-md object-cover" />
                    <p class="text-xs text-muted-foreground">Upload a new image below to replace this one.</p>
                </div>

                <FormField :label="currentImage ? 'Replace Image' : 'Image File *'" for="image" :error="form.errors.image">
                    <input
                        id="image"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        class="block w-full text-sm text-muted-foreground file:mr-4 file:rounded-md file:border-0 file:bg-primary file:px-4 file:py-2 file:text-sm file:font-medium file:text-primary-foreground hover:file:bg-primary/90"
                        @change="onFileChange"
                    />
                    <p class="text-xs text-muted-foreground">
                        JPG, PNG, or WebP. Max 10 MB.
                        <template v-if="!currentImage">Recommended: 1920×1080 or higher.</template>
                        Location and camera data is removed on upload.
                    </p>
                </FormField>

                <div v-if="previewUrl" class="space-y-1">
                    <Label>{{ currentImage ? 'New Image Preview' : 'Preview' }}</Label>
                    <img :src="previewUrl" alt="Preview of the selected image" class="h-40 w-full rounded-md object-cover" />
                </div>
            </CardContent>
        </Card>

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

        <div class="flex items-center justify-end gap-3">
            <Link :href="route('admin.backgrounds.index')" class="text-sm text-muted-foreground hover:text-foreground">
                Cancel
            </Link>
            <Button type="submit" :disabled="form.processing">
                {{ form.processing ? 'Saving...' : submitLabel }}
            </Button>
        </div>
    </form>
</template>
