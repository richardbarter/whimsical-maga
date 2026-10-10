<script setup lang="ts">
import type { Subject, PaginatedData } from '@/types';
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
import SubjectTableRow from './components/SubjectTableRow.vue';

defineProps<{
    subjects: PaginatedData<Subject>;
}>();

const deletion = useDeleteConfirmation<Subject>('admin.subjects.destroy');
</script>

<template>
    <Head title="Subjects" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-foreground">Subjects</h2>
                <Button as-child size="sm">
                    <Link :href="route('admin.subjects.create')">Add Subject</Link>
                </Button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <Card class="overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Title</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-center">Quotes</TableHead>
                                <TableHead class="hidden sm:table-cell">Last Updated</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableEmpty v-if="!subjects.data.length" :colspan="5">
                                <span>No subjects yet. <Link :href="route('admin.subjects.create')" class="underline">Add the first one.</Link></span>
                            </TableEmpty>

                            <SubjectTableRow
                                v-for="subject in subjects.data"
                                :key="subject.id"
                                :subject="subject"
                                @confirm-delete="deletion.confirmDelete"
                            />
                        </TableBody>
                    </Table>

                    <PaginationBar :paginator="subjects" route-name="admin.subjects.index" item-label="subjects" />
                </Card>
            </div>
        </div>
    </AdminLayout>

    <ConfirmDeleteDialog
        v-model:open="deletion.isOpen.value"
        title="Delete Subject"
        description="Are you sure you want to delete this subject? It will be removed from every quote that uses it."
        :processing="deletion.isDeleting.value"
        @confirm="deletion.executeDelete"
    >
        <p v-if="deletion.target.value" class="rounded-md bg-muted p-3 text-sm text-muted-foreground">
            {{ deletion.target.value.name }}
            <template v-if="deletion.target.value.quotes_count">
                (used by {{ deletion.target.value.quotes_count }}
                {{ deletion.target.value.quotes_count === 1 ? 'quote' : 'quotes' }})
            </template>
        </p>
    </ConfirmDeleteDialog>
</template>
