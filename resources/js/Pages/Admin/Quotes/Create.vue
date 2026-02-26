<script setup lang="ts">
import type { Tag, Category, Speaker } from '@/types';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import QuoteForm from './components/QuoteForm.vue';

defineProps<{
    tags: Tag[];
    categories: Category[];
    speakers: Speaker[];
}>();
</script>

<template>
    <Head title="Add Quote" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Add Quote
                </h2>
                <Link
                    :href="route('admin.quotes.index')"
                    class="text-sm text-gray-600 hover:text-gray-900"
                >
                    &larr; Back to Quotes
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <QuoteForm
                    :tags="tags"
                    :categories="categories"
                    :speakers="speakers"
                    submit-label="Create Quote"
                    submit-method="post"
                    :submit-route="route('admin.quotes.store')"
                />
            </div>
        </div>
    </AdminLayout>
</template>
