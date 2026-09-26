<script setup lang="ts">
import type { Tag, SavedContext, SavedContextFormData } from '@/types';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import SavedContextForm from './components/SavedContextForm.vue';

const props = defineProps<{
    savedContext: SavedContext;
    tags: Tag[];
}>();

const initialValues: SavedContextFormData = {
    subject: props.savedContext.subject,
    body: props.savedContext.body,
    tags: (props.savedContext.tags ?? []).map(t => ({ id: t.id, name: t.name })),
};
</script>

<template>
    <Head title="Edit Saved Context" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Edit Saved Context
                </h2>
                <Link
                    :href="route('admin.saved-contexts.index')"
                    class="text-sm text-gray-600 hover:text-gray-900"
                >
                    &larr; Back to Saved Contexts
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <SavedContextForm
                    :tags="tags"
                    :initial-values="initialValues"
                    submit-label="Update Saved Context"
                    submit-method="put"
                    :submit-route="route('admin.saved-contexts.update', savedContext.id)"
                />

                <!-- Read-only metadata -->
                <div class="mt-6 rounded-lg border bg-muted/40 px-4 py-3 text-sm text-muted-foreground">
                    <dl class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="font-medium">Created</dt>
                            <dd class="mt-0.5">{{ new Date(savedContext.created_at).toLocaleDateString() }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium">Last Updated</dt>
                            <dd class="mt-0.5">{{ new Date(savedContext.updated_at).toLocaleDateString() }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
