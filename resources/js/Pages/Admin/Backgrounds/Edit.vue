<script setup lang="ts">
import type { Background, BackgroundFormData } from '@/types';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { formatDate, formatFileSize } from '@/lib/utils';
import BackgroundForm from './components/BackgroundForm.vue';

const props = defineProps<{
    background: Background;
}>();

const initialValues: Partial<BackgroundFormData> = {
    title: props.background.title ?? '',
    alt_text: props.background.alt_text ?? '',
    description: props.background.description ?? '',
    credit: props.background.credit ?? '',
    source_url: props.background.source_url ?? '',
};
</script>

<template>
    <Head title="Edit Background" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-foreground">Edit Background</h2>
                <Link :href="route('admin.backgrounds.index')" class="text-sm text-muted-foreground hover:text-foreground">
                    &larr; Back to Backgrounds
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <BackgroundForm
                    :initial-values="initialValues"
                    :current-image="{ url: background.url, alt: background.alt_text ?? '' }"
                    submit-label="Update Background"
                    submit-method="put"
                    :submit-route="route('admin.backgrounds.update', background.id)"
                />

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
