<script setup lang="ts">
import type { Background } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { TableRow, TableCell } from '@/Components/ui/table';
import { Pencil, Trash2 } from 'lucide-vue-next';
import { formatDate, formatFileSize } from '@/lib/utils';

const props = defineProps<{ background: Background }>();
const emit = defineEmits<{
    confirmDelete: [background: Background];
    previewImage: [background: Background];
}>();
</script>

<template>
    <TableRow>
        <TableCell>
            <img
                :src="background.url"
                :alt="background.alt_text ?? ''"
                class="h-12 w-20 cursor-pointer rounded object-cover transition-opacity hover:opacity-80"
                @click="emit('previewImage', background)"
            />
        </TableCell>
        <TableCell class="text-sm text-gray-900">
            {{ background.title ?? '—' }}
        </TableCell>
        <TableCell class="text-sm text-gray-600">
            {{ background.dimensions ?? '—' }}
        </TableCell>
        <TableCell class="text-sm text-gray-600">
            {{ formatFileSize(background.file_size) }}
        </TableCell>
        <TableCell class="text-sm text-gray-600">
            {{ formatDate(background.created_at) }}
        </TableCell>
        <TableCell class="text-right">
            <div class="flex items-center justify-end gap-1">
                <Link :href="route('admin.backgrounds.edit', background.id)">
                    <Button variant="ghost" size="icon" title="Edit">
                        <Pencil class="h-4 w-4" />
                    </Button>
                </Link>
                <Button
                    variant="ghost"
                    size="icon"
                    class="text-destructive hover:text-destructive"
                    title="Delete"
                    @click="emit('confirmDelete', background)"
                >
                    <Trash2 class="h-4 w-4" />
                </Button>
            </div>
        </TableCell>
    </TableRow>
</template>
