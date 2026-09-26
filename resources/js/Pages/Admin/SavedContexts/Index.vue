<script setup lang="ts">
import type { SavedContext, PaginatedData } from '@/types';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import {
    Table,
    TableBody,
    TableHead,
    TableHeader,
    TableRow,
    TableCell,
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

const props = defineProps<{
    savedContexts: PaginatedData<SavedContext>;
}>();

const deleteTarget = ref<SavedContext | null>(null);
const deleting = ref(false);

function confirmDelete(savedContext: SavedContext) {
    deleteTarget.value = savedContext;
}

function executeDelete() {
    if (!deleteTarget.value) { return; }
    deleting.value = true;
    router.delete(route('admin.saved-contexts.destroy', deleteTarget.value.id), {
        onFinish: () => {
            deleteTarget.value = null;
            deleting.value = false;
        },
    });
}
</script>

<template>
    <Head title="Saved Contexts" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Saved Contexts
                </h2>
                <Link :href="route('admin.saved-contexts.create')">
                    <Button size="sm">Add Saved Context</Button>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
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

                            <TableRow v-for="savedContext in savedContexts.data" :key="savedContext.id">
                                <TableCell class="font-medium">
                                    {{ savedContext.subject }}
                                </TableCell>
                                <TableCell>
                                    <div class="flex flex-wrap gap-1">
                                        <Badge
                                            v-for="tag in savedContext.tags"
                                            :key="tag.id"
                                            variant="secondary"
                                            class="text-xs"
                                        >
                                            {{ tag.name }}
                                        </Badge>
                                        <span v-if="!savedContext.tags?.length" class="text-xs text-muted-foreground">—</span>
                                    </div>
                                </TableCell>
                                <TableCell class="hidden max-w-xs md:table-cell">
                                    <p class="truncate text-sm text-muted-foreground">{{ savedContext.body }}</p>
                                </TableCell>
                                <TableCell class="hidden whitespace-nowrap text-sm text-muted-foreground sm:table-cell">
                                    {{ new Date(savedContext.created_at).toLocaleDateString() }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="route('admin.saved-contexts.edit', savedContext.id)">
                                            <Button variant="outline" size="sm">Edit</Button>
                                        </Link>
                                        <Button variant="destructive" size="sm" @click="confirmDelete(savedContext)">
                                            Delete
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <!-- Pagination -->
                    <div v-if="savedContexts.last_page > 1" class="flex items-center justify-between border-t px-4 py-3">
                        <p class="text-sm text-muted-foreground">
                            Showing {{ savedContexts.data.length }} of {{ savedContexts.total }} saved contexts
                        </p>
                        <div class="flex gap-2">
                            <Link
                                v-if="savedContexts.current_page > 1"
                                :href="route('admin.saved-contexts.index', { page: savedContexts.current_page - 1 })"
                            >
                                <Button variant="outline" size="sm">Previous</Button>
                            </Link>
                            <Link
                                v-if="savedContexts.current_page < savedContexts.last_page"
                                :href="route('admin.saved-contexts.index', { page: savedContexts.current_page + 1 })"
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
                <DialogTitle>Delete Saved Context</DialogTitle>
                <DialogDescription>
                    Are you sure you want to delete this saved context? This action cannot be undone.
                </DialogDescription>
            </DialogHeader>
            <p v-if="deleteTarget" class="rounded-md bg-muted p-3 text-sm text-muted-foreground">
                {{ deleteTarget.subject }}
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
