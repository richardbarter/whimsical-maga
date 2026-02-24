<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    tags?: Array<{
        id: number;
        name: string;
        slug: string;
    }>;
}>();
</script>

<template>
    <Head title="Manage Tags" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Tags
                </h2>
                <Link
                    :href="route('admin.tags.create')"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                >
                    Add Tag
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <p class="text-gray-500">Tag management coming soon...</p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
