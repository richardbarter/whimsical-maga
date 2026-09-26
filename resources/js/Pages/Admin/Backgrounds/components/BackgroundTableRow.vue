<script setup lang="ts">
import type { Background } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { TableRow, TableCell } from '@/Components/ui/table';
import { Pencil, Trash2 } from 'lucide-vue-next';
import { formatDate, formatFileSize } from '@/lib/utils';

defineProps<{ background: Background }>();
const emit = defineEmits<{
    confirmDelete: [background: Background];
    previewImage: [background: Background];
}>();
</script>

<template>
    <TableRow>
        <TableCell>
            <button
                type="button"
                class="block rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                :aria-label="`Preview ${background.title ?? 'background'}`"
                @click="emit('previewImage', background)"
            >
                <img
                    :src="background.url"
                    :alt="background.alt_text ?? ''"
                    class="h-12 w-20 rounded object-cover transition-opacity hover:opacity-80"
                />
            </button>
        </TableCell>
        <TableCell class="text-sm text-foreground">
            {{ background.title ?? '—' }}
        </TableCell>
        <TableCell class="text-sm text-muted-foreground">
            {{ background.dimensions ?? '—' }}
        </TableCell>
        <TableCell class="text-sm text-muted-foreground">
            {{ formatFileSize(background.file_size) }}
        </TableCell>
        <TableCell class="text-sm text-muted-foreground">
            {{ formatDate(background.created_at) }}
        </TableCell>
        <TableCell class="text-right">
            <div class="flex items-center justify-end gap-1">
                <Button as-child variant="ghost" size="icon">
                    <Link :href="route('admin.backgrounds.edit', background.id)" title="Edit" aria-label="Edit background">
                        <Pencil class="h-4 w-4" />
                    </Link>
                </Button>
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
