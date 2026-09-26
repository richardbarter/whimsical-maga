<script setup lang="ts">
import type { Quote, PaginatedData } from '@/types';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Card } from '@/Components/ui/card';
import { ConfirmDeleteDialog } from '@/Components/ui/confirm-delete-dialog';
import { PaginationBar } from '@/Components/ui/pagination-bar';
import {
    Table,
    TableBody,
    TableHead,
    TableHeader,
    TableRow,
    TableEmpty,
} from '@/Components/ui/table';
import { TooltipProvider } from '@/Components/ui/tooltip';
import { useDeleteConfirmation } from '@/composables/useDeleteConfirmation';
import { truncate } from '@/lib/utils';
import QuoteTableRow from './components/QuoteTableRow.vue';

defineProps<{
    quotes: PaginatedData<Quote>;
}>();

const DIALOG_PREVIEW_LENGTH = 120;

const deletion = useDeleteConfirmation<Quote>('admin.quotes.destroy');
</script>

<template>
    <Head title="Manage Quotes" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-foreground">Quotes</h2>
                <Button as-child size="sm">
                    <Link :href="route('admin.quotes.create')">Add Quote</Link>
                </Button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <Card class="overflow-hidden">
                    <TooltipProvider :delay-duration="300">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-[40%]">Quote</TableHead>
                                    <TableHead>Speaker</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead class="text-center">Verified</TableHead>
                                    <TableHead class="text-center">Featured</TableHead>
                                    <TableHead>Occurred At</TableHead>
                                    <TableHead>Created</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableEmpty v-if="!quotes.data.length" :colspan="8">
                                    No quotes yet. <Link :href="route('admin.quotes.create')" class="underline">Add the first one.</Link>
                                </TableEmpty>

                                <QuoteTableRow
                                    v-for="quote in quotes.data"
                                    :key="quote.id"
                                    :quote="quote"
                                    @confirm-delete="deletion.confirmDelete"
                                />
                            </TableBody>
                        </Table>
                    </TooltipProvider>

                    <PaginationBar :paginator="quotes" route-name="admin.quotes.index" item-label="quotes" />
                </Card>
            </div>
        </div>
    </AdminLayout>

    <ConfirmDeleteDialog
        v-model:open="deletion.isOpen.value"
        title="Delete Quote"
        description="Are you sure you want to delete this quote? This action cannot be undone."
        :processing="deletion.isDeleting.value"
        @confirm="deletion.executeDelete"
    >
        <p v-if="deletion.target.value" class="rounded-md bg-muted p-3 text-sm italic text-muted-foreground">
            "{{ truncate(deletion.target.value.text, DIALOG_PREVIEW_LENGTH) }}"
        </p>
    </ConfirmDeleteDialog>
</template>
