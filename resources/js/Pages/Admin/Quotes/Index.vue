<script setup lang="ts">
import type { Quote, PaginatedData } from '@/types';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/Components/ui/button';
import {
    Table,
    TableBody,
    TableHead,
    TableHeader,
    TableRow,
    TableEmpty,
} from '@/Components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { TooltipProvider } from '@/Components/ui/tooltip';
import QuoteTableRow from './components/QuoteTableRow.vue';

const props = defineProps<{
    quotes: PaginatedData<Quote>;
}>();

const DIALOG_PREVIEW_LENGTH = 120;

const deleteTarget = ref<Quote | null>(null);
const deleting = ref(false);

function truncate(text: string, length: number): string {
    return text.length > length ? text.slice(0, length) + '…' : text;
}

function confirmDelete(quote: Quote) {
    deleteTarget.value = quote;
}

function executeDelete() {
    if (!deleteTarget.value) return;
    deleting.value = true;
    router.delete(route('admin.quotes.destroy', deleteTarget.value.id), {
        onFinish: () => {
            deleteTarget.value = null;
            deleting.value = false;
        },
    });
}
</script>

<template>
    <Head title="Manage Quotes" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Quotes
                </h2>
                <Link :href="route('admin.quotes.create')">
                    <Button size="sm">Add Quote</Button>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <TooltipProvider :delay-duration="300">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-[40%]">Quote</TableHead>
                                <TableHead>Speaker</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-center">Verified</TableHead>
                                <TableHead class="text-center">Featured</TableHead>
                                <TableHead>Date</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableEmpty v-if="!quotes.data.length" :colspan="7">
                                No quotes yet. <Link :href="route('admin.quotes.create')" class="underline">Add the first one.</Link>
                            </TableEmpty>

                            <QuoteTableRow
                                v-for="quote in quotes.data"
                                :key="quote.id"
                                :quote="quote"
                                @confirm-delete="confirmDelete"
                            />
                        </TableBody>
                    </Table>
                    </TooltipProvider>

                    <!-- Pagination -->
                    <div v-if="quotes.last_page > 1" class="flex items-center justify-between border-t px-4 py-3">
                        <p class="text-sm text-muted-foreground">
                            Showing {{ quotes.data.length }} of {{ quotes.total }} quotes
                        </p>
                        <div class="flex gap-2">
                            <Link
                                v-if="quotes.current_page > 1"
                                :href="route('admin.quotes.index', { page: quotes.current_page - 1 })"
                            >
                                <Button variant="outline" size="sm">Previous</Button>
                            </Link>
                            <Link
                                v-if="quotes.current_page < quotes.last_page"
                                :href="route('admin.quotes.index', { page: quotes.current_page + 1 })"
                            >
                                <Button variant="outline" size="sm">Next</Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>

    <!-- Delete confirmation dialog -->
    <Dialog :open="!!deleteTarget" @update:open="val => { if (!val) deleteTarget = null }">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Delete Quote</DialogTitle>
                <DialogDescription>
                    Are you sure you want to delete this quote? This action cannot be undone.
                </DialogDescription>
            </DialogHeader>
            <p v-if="deleteTarget" class="rounded-md bg-muted p-3 text-sm italic text-muted-foreground">
                "{{ truncate(deleteTarget.text, DIALOG_PREVIEW_LENGTH) }}"
            </p>
            <DialogFooter>
                <Button variant="outline" :disabled="deleting" @click="deleteTarget = null">Cancel</Button>
                <Button variant="destructive" :disabled="deleting" @click="executeDelete">
                    {{ deleting ? 'Deleting...' : 'Delete' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
