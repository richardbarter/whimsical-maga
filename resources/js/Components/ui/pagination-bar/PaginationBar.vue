<script setup lang="ts">
import type { PaginatedData } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';

defineProps<{
    paginator: PaginatedData<unknown>;
    routeName: string;
    /** Plural noun for the summary line, e.g. "quotes". */
    itemLabel: string;
}>();
</script>

<template>
    <div v-if="paginator.last_page > 1" class="flex items-center justify-between border-t px-4 py-3">
        <p class="text-sm text-muted-foreground">
            Showing {{ paginator.data.length }} of {{ paginator.total }} {{ itemLabel }}
        </p>
        <div class="flex gap-2">
            <Button v-if="paginator.current_page > 1" as-child variant="outline" size="sm">
                <Link :href="route(routeName, { page: paginator.current_page - 1 })">Previous</Link>
            </Button>
            <Button v-if="paginator.current_page < paginator.last_page" as-child variant="outline" size="sm">
                <Link :href="route(routeName, { page: paginator.current_page + 1 })">Next</Link>
            </Button>
        </div>
    </div>
</template>
