<script setup lang="ts">
import type { SavedContext } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { TableCell, TableRow } from '@/Components/ui/table';
import { formatDate } from '@/lib/utils';

defineProps<{ savedContext: SavedContext }>();

const emit = defineEmits<{ confirmDelete: [savedContext: SavedContext] }>();
</script>

<template>
    <TableRow>
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
            {{ formatDate(savedContext.created_at) }}
        </TableCell>
        <TableCell class="text-right">
            <div class="flex items-center justify-end gap-2">
                <Button as-child variant="outline" size="sm">
                    <Link :href="route('admin.saved-contexts.edit', savedContext.id)">Edit</Link>
                </Button>
                <Button variant="destructive" size="sm" @click="emit('confirmDelete', savedContext)">
                    Delete
                </Button>
            </div>
        </TableCell>
    </TableRow>
</template>
