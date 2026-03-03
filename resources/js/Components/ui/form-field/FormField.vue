<script setup lang="ts">
import { Label } from '@/Components/ui/label';

defineProps<{
    label: string;
    for?: string;
    error?: string;
}>();
</script>

<template>
    <div class="space-y-2">
        <Label :for="$props.for">{{ label }}</Label>
        <slot />
        <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
    </div>
</template>
