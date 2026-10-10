<script setup lang="ts">
import type { BadgeVariants } from '@/Components/ui/badge';
import type { QuoteStatus, SubjectStatus } from '@/types';
import { Badge } from '@/Components/ui/badge';

type Status = QuoteStatus | SubjectStatus;

/** Published is green, pending yellow, draft grey — the same on every admin table. */
const STATUS_STYLES: Record<Status, { variant: BadgeVariants['variant']; class: string }> = {
    published: { variant: 'default', class: 'bg-green-100 text-green-800 hover:bg-green-100' },
    pending: { variant: 'secondary', class: 'bg-yellow-100 text-yellow-800 hover:bg-yellow-100' },
    draft: { variant: 'outline', class: 'bg-gray-100 text-gray-600 hover:bg-gray-100' },
};

defineProps<{
    status: Status;
}>();
</script>

<template>
    <Badge :variant="STATUS_STYLES[status].variant" :class="STATUS_STYLES[status].class">
        {{ status }}
    </Badge>
</template>
