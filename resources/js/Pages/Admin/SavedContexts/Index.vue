<script setup lang="ts">
import type { SavedContext, PaginatedData } from '@/types';
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
import { useDeleteConfirmation } from '@/composables/useDeleteConfirmation';
import SavedContextTableRow from './components/SavedContextTableRow.vue';

defineProps<{
    savedContexts: PaginatedData<SavedContext>;
}>();

const deletion = useDeleteConfirmation<SavedContext>('admin.saved-contexts.destroy');
</script>

<template>
    <Head title="Saved Contexts" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-foreground">Saved Contexts</h2>
                <Button as-child size="sm">
                    <Link :href="route('admin.saved-contexts.create')">Add Saved Context</Link>
                </Button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <Card class="overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Subject</TableHead>
                                <TableHead>Tags</TableHead>
                                <TableHead class="hidden md:table-cell">Body Preview</TableHead>
                                <TableHead class="hidden sm:table-cell">Created</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableEmpty v-if="!savedContexts.data.length" :colspan="5">
                                No saved contexts yet. <Link :href="route('admin.saved-contexts.create')" class="underline">Add the first one.</Link>
                            </TableEmpty>

                            <SavedContextTableRow
                                v-for="savedContext in savedContexts.data"
                                :key="savedContext.id"
                                :saved-context="savedContext"
                                @confirm-delete="deletion.confirmDelete"
                            />
                        </TableBody>
                    </Table>

                    <PaginationBar :paginator="savedContexts" route-name="admin.saved-contexts.index" item-label="saved contexts" />
                </Card>
            </div>
        </div>
    </AdminLayout>

    <ConfirmDeleteDialog
        v-model:open="deletion.isOpen.value"
        title="Delete Saved Context"
        description="Are you sure you want to delete this saved context? This action cannot be undone."
        :processing="deletion.isDeleting.value"
        @confirm="deletion.executeDelete"
    >
        <p v-if="deletion.target.value" class="rounded-md bg-muted p-3 text-sm text-muted-foreground">
            {{ deletion.target.value.subject }}
        </p>
    </ConfirmDeleteDialog>
</template>
