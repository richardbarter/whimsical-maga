<script setup lang="ts">
import type { Background, PaginatedData } from '@/types';
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
import BackgroundTableRow from './components/BackgroundTableRow.vue';

const props = defineProps<{
    backgrounds: PaginatedData<Background>;
}>();

const deleteTarget = ref<Background | null>(null);
const deleting = ref(false);
const previewTarget = ref<Background | null>(null);

function confirmDelete(background: Background) {
    deleteTarget.value = background;
}

function previewImage(background: Background) {
    previewTarget.value = background;
}

function executeDelete() {
    if (!deleteTarget.value) return;
    deleting.value = true;
    router.delete(route('admin.backgrounds.destroy', deleteTarget.value.id), {
        onFinish: () => {
            deleteTarget.value = null;
            deleting.value = false;
        },
    });
}
</script>

<template>
    <Head title="Manage Backgrounds" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Backgrounds
                </h2>
                <Link :href="route('admin.backgrounds.create')">
                    <Button size="sm">Add Background</Button>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-24">Image</TableHead>
                                <TableHead>Title</TableHead>
                                <TableHead>Dimensions</TableHead>
                                <TableHead>File Size</TableHead>
                                <TableHead>Date Added</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableEmpty v-if="!backgrounds.data.length" :colspan="6">
                                No backgrounds yet. <Link :href="route('admin.backgrounds.create')" class="underline">Add the first one.</Link>
                            </TableEmpty>

                            <BackgroundTableRow
                                v-for="background in backgrounds.data"
                                :key="background.id"
                                :background="background"
                                @confirm-delete="confirmDelete"
                                @preview-image="previewImage"
                            />
                        </TableBody>
                    </Table>

                    <!-- Pagination -->
                    <div v-if="backgrounds.last_page > 1" class="flex items-center justify-between border-t px-4 py-3">
                        <p class="text-sm text-muted-foreground">
                            Showing {{ backgrounds.data.length }} of {{ backgrounds.total }} backgrounds
                        </p>
                        <div class="flex gap-2">
                            <Link
                                v-if="backgrounds.current_page > 1"
                                :href="route('admin.backgrounds.index', { page: backgrounds.current_page - 1 })"
                            >
                                <Button variant="outline" size="sm">Previous</Button>
                            </Link>
                            <Link
                                v-if="backgrounds.current_page < backgrounds.last_page"
                                :href="route('admin.backgrounds.index', { page: backgrounds.current_page + 1 })"
                            >
                                <Button variant="outline" size="sm">Next</Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>

    <!-- Image preview lightbox -->
    <Dialog :open="!!previewTarget" @update:open="val => { if (!val) previewTarget = null }">
        <DialogContent class="max-w-[92vw] sm:max-w-[92vw] p-0 overflow-hidden [&_[data-slot=dialog-close]]:text-gray-300 [&_[data-slot=dialog-close]]:hover:text-white">
            <DialogTitle class="sr-only">
                {{ previewTarget?.title ?? 'Background preview' }}
            </DialogTitle>
            <img
                v-if="previewTarget"
                :src="previewTarget.url"
                :alt="previewTarget.alt_text ?? ''"
                class="w-full max-h-[85vh] object-contain bg-black"
            />
            <div v-if="previewTarget?.title || previewTarget?.credit" class="px-4 py-3 text-sm text-muted-foreground">
                <span v-if="previewTarget?.title" class="font-medium text-foreground">{{ previewTarget.title }}</span>
                <span v-if="previewTarget?.title && previewTarget?.credit"> · </span>
                <span v-if="previewTarget?.credit">{{ previewTarget.credit }}</span>
            </div>
        </DialogContent>
    </Dialog>

    <!-- Delete confirmation dialog -->
    <Dialog :open="!!deleteTarget" @update:open="val => { if (!val) deleteTarget = null }">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Delete Background</DialogTitle>
                <DialogDescription>
                    Are you sure you want to delete this background? This action cannot be undone.
                </DialogDescription>
            </DialogHeader>
            <p v-if="deleteTarget" class="rounded-md bg-muted p-3 text-sm text-muted-foreground">
                {{ deleteTarget.title ?? 'This background' }}
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
