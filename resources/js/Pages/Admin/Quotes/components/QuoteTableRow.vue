<script setup lang="ts">
import type { Quote } from '@/types';
import { router } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { formatDate } from '@/lib/utils';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { TableRow, TableCell } from '@/Components/ui/table';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/Components/ui/tooltip';
import { Pencil, ShieldCheck, Star, Trash2, Check, Minus } from 'lucide-vue-next';

const props = defineProps<{ quote: Quote }>();
const emit = defineEmits<{ confirmDelete: [quote: Quote] }>();

const TRUNCATE_LENGTH = 100;

function truncate(text: string, length = TRUNCATE_LENGTH): string {
    return text.length > length ? text.slice(0, length) + '…' : text;
}

function statusVariant(status: Quote['status']): 'default' | 'secondary' | 'outline' {
    if (status === 'published') return 'default';
    if (status === 'pending') return 'secondary';
    return 'outline';
}

function toggleVerified() {
    router.patch(route('admin.quotes.verify', props.quote.id), {}, { preserveScroll: true });
}

function toggleFeature() {
    router.patch(route('admin.quotes.feature', props.quote.id), {}, { preserveScroll: true });
}

</script>

<template>
    <TableRow>
        <TableCell class="max-w-xs text-sm text-gray-900">
            <Tooltip v-if="quote.text.length > TRUNCATE_LENGTH">
                <TooltipTrigger class="cursor-default text-left">
                    {{ truncate(quote.text) }}
                </TooltipTrigger>
                <TooltipContent class="max-w-sm whitespace-pre-wrap text-xs leading-relaxed">
                    {{ quote.text }}
                </TooltipContent>
            </Tooltip>
            <template v-else>{{ quote.text }}</template>
        </TableCell>
        <TableCell class="text-sm text-gray-600">
            {{ quote.speaker?.name ?? '—' }}
        </TableCell>
        <TableCell>
            <Badge
                :variant="statusVariant(quote.status)"
                :class="{
                    'bg-green-100 text-green-800 hover:bg-green-100': quote.status === 'published',
                    'bg-yellow-100 text-yellow-800 hover:bg-yellow-100': quote.status === 'pending',
                    'bg-gray-100 text-gray-600 hover:bg-gray-100': quote.status === 'draft',
                }"
            >
                {{ quote.status }}
            </Badge>
        </TableCell>
        <TableCell class="text-center">
            <Check v-if="quote.is_verified" class="mx-auto h-4 w-4 text-green-600" />
            <Minus v-else class="mx-auto h-4 w-4 text-muted-foreground" />
        </TableCell>
        <TableCell class="text-center">
            <Check v-if="quote.is_featured" class="mx-auto h-4 w-4 text-green-600" />
            <Minus v-else class="mx-auto h-4 w-4 text-muted-foreground" />
        </TableCell>
        <TableCell class="text-sm text-gray-600">
            {{ quote.occurred_at ? formatDate(quote.occurred_at) : '—' }}
        </TableCell>
        <TableCell class="text-sm text-gray-600">
            {{ formatDate(quote.created_at) }}
        </TableCell>
        <TableCell class="text-right">
            <div class="flex items-center justify-end gap-1">
                <Link :href="route('admin.quotes.edit', quote.id)">
                    <Button variant="ghost" size="icon" title="Edit">
                        <Pencil class="h-4 w-4" />
                    </Button>
                </Link>
                <Button
                    variant="ghost"
                    size="icon"
                    :title="quote.is_verified ? 'Remove verification' : 'Mark verified'"
                    :class="{ 'text-green-600': quote.is_verified }"
                    @click="toggleVerified"
                >
                    <ShieldCheck class="h-4 w-4" />
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    :title="quote.is_featured ? 'Remove feature' : 'Mark featured'"
                    :class="{ 'text-yellow-500': quote.is_featured }"
                    @click="toggleFeature"
                >
                    <Star class="h-4 w-4" />
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    class="text-destructive hover:text-destructive"
                    title="Delete"
                    @click="emit('confirmDelete', quote)"
                >
                    <Trash2 class="h-4 w-4" />
                </Button>
            </div>
        </TableCell>
    </TableRow>
</template>
