<script setup lang="ts">
import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';

defineProps<{
    title: string;
    description: string;
    processing?: boolean;
}>();

const emit = defineEmits<{
    confirm: [];
}>();

const open = defineModel<boolean>('open', { required: true });
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>

            <!-- Preview of the item being deleted -->
            <slot />

            <DialogFooter>
                <Button variant="outline" :disabled="processing" @click="open = false">Cancel</Button>
                <Button variant="destructive" :disabled="processing" @click="emit('confirm')">
                    {{ processing ? 'Deleting...' : 'Delete' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
