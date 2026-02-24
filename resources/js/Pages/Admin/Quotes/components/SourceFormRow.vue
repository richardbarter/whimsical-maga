<script setup lang="ts">
import type { SourceForm } from '@/types'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import { Switch } from '@/Components/ui/switch'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select'
import { Trash2 } from 'lucide-vue-next'

const props = defineProps<{
    source: SourceForm
    index: number
    errors: Record<string, string>
}>()

const emit = defineEmits<{
    'update:source': [value: SourceForm]
    remove: []
}>()

function update(field: keyof SourceForm, value: string | number | boolean) {
    emit('update:source', { ...props.source, [field]: value })
}

const sourceTypes = [
    { value: 'tweet', label: 'Tweet' },
    { value: 'article', label: 'Article' },
    { value: 'video', label: 'Video' },
    { value: 'speech', label: 'Speech' },
    { value: 'interview', label: 'Interview' },
    { value: 'press_conference', label: 'Press Conference' },
    { value: 'rally', label: 'Rally' },
    { value: 'social_media', label: 'Social Media' },
    { value: 'book', label: 'Book' },
    { value: 'other', label: 'Other' },
]
</script>

<template>
    <div class="space-y-3 rounded-lg border p-4">
        <div class="flex items-center justify-between">
            <span class="text-sm font-medium">Source {{ index + 1 }}</span>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                @click="$emit('remove')"
            >
                <Trash2 class="h-4 w-4 text-destructive" />
            </Button>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            <div class="space-y-2 sm:col-span-2">
                <Label :for="`source-url-${index}`">URL *</Label>
                <Input
                    :id="`source-url-${index}`"
                    :model-value="source.url"
                    @update:model-value="update('url', $event)"
                    type="url"
                    placeholder="https://..."
                />
                <p v-if="errors[`sources.${index}.url`]" class="text-sm text-destructive">
                    {{ errors[`sources.${index}.url`] }}
                </p>
            </div>

            <div class="space-y-2">
                <Label :for="`source-title-${index}`">Title</Label>
                <Input
                    :id="`source-title-${index}`"
                    :model-value="source.title"
                    @update:model-value="update('title', $event)"
                    placeholder="Source title"
                />
            </div>

            <div class="space-y-2">
                <Label>Type</Label>
                <Select :model-value="source.source_type" @update:model-value="val => update('source_type', String(val ?? ''))">
                    <SelectTrigger>
                        <SelectValue placeholder="Select type" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="type in sourceTypes"
                            :key="type.value"
                            :value="type.value"
                        >
                            {{ type.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div class="space-y-2 sm:col-span-2">
                <Label :for="`source-archived-${index}`">Archived URL</Label>
                <Input
                    :id="`source-archived-${index}`"
                    :model-value="source.archived_url"
                    @update:model-value="update('archived_url', $event)"
                    type="url"
                    placeholder="https://web.archive.org/..."
                />
            </div>

            <div class="flex items-center gap-2">
                <Switch
                    :id="`source-primary-${index}`"
                    :checked="source.is_primary"
                    @update:checked="update('is_primary', $event)"
                />
                <Label :for="`source-primary-${index}`">Primary source</Label>
            </div>
        </div>
    </div>
</template>
