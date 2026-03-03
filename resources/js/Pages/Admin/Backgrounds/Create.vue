<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import FormField from '@/Components/ui/form-field/FormField.vue';

const form = useForm({
    image: null as File | null,
    title: '',
    alt_text: '',
    description: '',
    credit: '',
    source_url: '',
});

const imagePreview = ref<string | null>(null);

function onFileChange(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    form.image = file;
    if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    } else {
        imagePreview.value = null;
    }
}

function submit() {
    form.post(route('admin.backgrounds.store'), {
        forceFormData: true,
    });
}
</script>

<template>
    <Head title="Add Background" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Add Background
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
                    <!-- Image Upload -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Image</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <FormField label="Image File *" for="image" :error="form.errors.image">
                                <input
                                    id="image"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-primary file:px-4 file:py-2 file:text-sm file:font-medium file:text-primary-foreground hover:file:bg-primary/90"
                                    @change="onFileChange"
                                />
                                <p class="text-xs text-muted-foreground">JPG, PNG, or WebP. Max 10 MB. Recommended: 1920×1080 or higher.</p>
                            </FormField>

                            <div v-if="imagePreview" class="space-y-1">
                                <Label>Preview</Label>
                                <img
                                    :src="imagePreview"
                                    alt="Preview"
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
                            {{ form.processing ? 'Uploading...' : 'Add Background' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
