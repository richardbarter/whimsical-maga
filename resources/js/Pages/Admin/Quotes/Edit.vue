<script setup lang="ts">
import type { Tag, Category, Speaker, Quote, QuoteFormData, QuoteTypeOption } from '@/types';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { formatDate } from '@/lib/utils';
import QuoteForm from './components/QuoteForm.vue';

const props = defineProps<{
    quote: Quote;
    tags: Tag[];
    categories: Category[];
    speakers: Speaker[];
    quoteTypes: QuoteTypeOption[];
}>();

const initialValues: QuoteFormData = {
    text: props.quote.text,
    speaker: props.quote.speaker?.name ?? '',
    quote_type: props.quote.quote_type ?? '',
    quote_type_note: props.quote.quote_type_note ?? '',
    context: props.quote.context ?? '',
    location: props.quote.location ?? '',
    occurred_at: props.quote.occurred_at ? props.quote.occurred_at.substring(0, 10) : '',
    is_verified: props.quote.is_verified,
    is_featured: props.quote.is_featured,
    status: props.quote.status,
    tags: (props.quote.tags ?? []).map(t => String(t.id)),
    categories: (props.quote.categories ?? []).map(c => String(c.id)),
    sources: (props.quote.sources ?? []).map(s => ({
        _key: crypto.randomUUID(),
        url: s.url,
        title: s.title ?? '',
        source_type: s.source_type ?? '',
        is_primary: s.is_primary,
        archived_url: s.archived_url ?? '',
    })),
};

</script>

<template>
    <Head title="Edit Quote" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Edit Quote
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
                    :quote-types="quoteTypes"
                    :initial-values="initialValues"
                    submit-label="Update Quote"
                    submit-method="put"
                    :submit-route="route('admin.quotes.update', quote.id)"
                />

                <!-- Read-only metadata -->
                <div class="mt-6 rounded-lg border bg-muted/40 px-4 py-3 text-sm text-muted-foreground">
                    <dl class="grid grid-cols-3 gap-4">
                        <div>
                            <dt class="font-medium">Slug</dt>
                            <dd class="mt-0.5 font-mono text-xs">{{ quote.slug }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium">View Count</dt>
                            <dd class="mt-0.5">{{ quote.view_count.toLocaleString() }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium">Created</dt>
                            <dd class="mt-0.5">{{ formatDate(quote.created_at) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
