<script setup lang="ts">
import type { Background, PaginatedData } from '@/types';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
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
import {
    Dialog,
    DialogContent,
    DialogTitle,
} from '@/Components/ui/dialog';
import { useDeleteConfirmation } from '@/composables/useDeleteConfirmation';
import BackgroundTableRow from './components/BackgroundTableRow.vue';

defineProps<{
    backgrounds: PaginatedData<Background>;
}>();

const deletion = useDeleteConfirmation<Background>('admin.backgrounds.destroy');
const previewTarget = ref<Background | null>(null);

function previewImage(background: Background): void {
    previewTarget.value = background;
}
</script>

<template>
    <Head title="Manage Backgrounds" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-foreground">Backgrounds</h2>
                <Button as-child size="sm">
                    <Link :href="route('admin.backgrounds.create')">Add Background</Link>
                </Button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <Card class="overflow-hidden">
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
                                @confirm-delete="deletion.confirmDelete"
                                @preview-image="previewImage"
                            />
                        </TableBody>
                    </Table>

                    <PaginationBar :paginator="backgrounds" route-name="admin.backgrounds.index" item-label="backgrounds" />
                </Card>
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

    <ConfirmDeleteDialog
        v-model:open="deletion.isOpen.value"
        title="Delete Background"
        description="Are you sure you want to delete this background? This action cannot be undone."
        :processing="deletion.isDeleting.value"
        @confirm="deletion.executeDelete"
    >
        <p v-if="deletion.target.value" class="rounded-md bg-muted p-3 text-sm text-muted-foreground">
            {{ deletion.target.value.title ?? 'This background' }}
        </p>
    </ConfirmDeleteDialog>
</template>
