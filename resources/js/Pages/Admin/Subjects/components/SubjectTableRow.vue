<script setup lang="ts">
import type { Subject } from '@/types';
import { Link } from '@inertiajs/vue3';
import { StatusBadge } from '@/Components/ui/status-badge';
import { Button } from '@/Components/ui/button';
import { TableCell, TableRow } from '@/Components/ui/table';
import { formatDate } from '@/lib/utils';

defineProps<{ subject: Subject }>();

const emit = defineEmits<{ confirmDelete: [subject: Subject] }>();
</script>

<template>
    <TableRow>
        <TableCell class="font-medium">
            {{ subject.name }}
        </TableCell>
        <TableCell>
            <StatusBadge :status="subject.status" />
        </TableCell>
        <TableCell class="text-center text-sm text-muted-foreground">
            {{ subject.quotes_count ?? 0 }}
        </TableCell>
        <TableCell class="hidden whitespace-nowrap text-sm text-muted-foreground sm:table-cell">
            {{ formatDate(subject.updated_at) }}
        </TableCell>
        <TableCell class="text-right">
            <div class="flex items-center justify-end gap-2">
                <Button as-child variant="outline" size="sm">
                    <Link :href="route('admin.subjects.edit', subject.id)">Edit</Link>
                </Button>
                <Button variant="destructive" size="sm" @click="emit('confirmDelete', subject)">
                    Delete
                </Button>
            </div>
        </TableCell>
    </TableRow>
</template>
