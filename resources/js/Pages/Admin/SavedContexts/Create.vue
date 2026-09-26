<script setup lang="ts">
import type { Tag } from '@/types';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import SavedContextForm from './components/SavedContextForm.vue';

defineProps<{
    tags: Tag[];
}>();
</script>

<template>
    <Head title="Add Saved Context" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-foreground">
                    Add Saved Context
                </h2>
                <Link
                    :href="route('admin.saved-contexts.index')"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to Saved Contexts
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <SavedContextForm
                    :tags="tags"
                    submit-label="Create Saved Context"
                    submit-method="post"
                    :submit-route="route('admin.saved-contexts.store')"
                />
            </div>
        </div>
    </AdminLayout>
</template>
