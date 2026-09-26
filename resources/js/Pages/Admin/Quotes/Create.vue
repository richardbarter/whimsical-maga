<script setup lang="ts">
import type { Tag, Category, Speaker, QuoteTypeOption, QuoteStatusOption, SourceTypeOption } from '@/types';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import QuoteForm from './components/QuoteForm.vue';

defineProps<{
    tags: Tag[];
    categories: Category[];
    speakers: Speaker[];
    quoteTypes: QuoteTypeOption[];
    quoteStatuses: QuoteStatusOption[];
    sourceTypes: SourceTypeOption[];
}>();
</script>

<template>
    <Head title="Add Quote" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-foreground">
                    Add Quote
                </h2>
                <Link
                    :href="route('admin.quotes.index')"
                    class="text-sm text-muted-foreground hover:text-foreground"
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
                    :quote-types="quoteTypes"
                    :quote-statuses="quoteStatuses"
                    :source-types="sourceTypes"
                    submit-label="Create Quote"
                    submit-method="post"
                    :submit-route="route('admin.quotes.store')"
                />
            </div>
        </div>
    </AdminLayout>
</template>
