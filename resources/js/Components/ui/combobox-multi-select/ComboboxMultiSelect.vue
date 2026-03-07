<script setup lang="ts">
import { ref } from 'vue'
import type { ComboboxItem } from '@/types'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Badge } from '@/Components/ui/badge'
import { Separator } from '@/Components/ui/separator'
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover'
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/Components/ui/command'
import { Plus, Check } from 'lucide-vue-next'

export interface ComboboxOption {
    id: number
    name: string
}

const modelValue = defineModel<ComboboxItem[]>({ required: true })

const props = withDefaults(defineProps<{
    options: ComboboxOption[]
    placeholder?: string
    searchPlaceholder?: string
    newItemPlaceholder?: string
    error?: string
}>(), {
    placeholder: 'Select items...',
    searchPlaceholder: 'Search...',
    newItemPlaceholder: 'New item name...',
    error: '',
})

const open = ref(false)
const newItemInput = ref('')

function isSelected(option: ComboboxOption): boolean {
    return modelValue.value.some(item => item.id === option.id)
}

function toggle(option: ComboboxOption): void {
    const current = [...modelValue.value]
    const index = current.findIndex(item => item.id === option.id)
    if (index === -1) {
        current.push({ id: option.id, name: option.name })
    } else {
        current.splice(index, 1)
    }
    modelValue.value = current
}

function addNew(): void {
    const name = newItemInput.value.trim()
    if (!name) { return }

    const current = [...modelValue.value]
    const existing = props.options.find(o => o.name.toLowerCase() === name.toLowerCase())

    if (existing) {
        if (!current.some(item => item.id === existing.id)) {
            current.push({ id: existing.id, name: existing.name })
            modelValue.value = current
        }
    } else if (!current.some(item => item.name.toLowerCase() === name.toLowerCase())) {
        current.push({ id: null, name })
        modelValue.value = current
    }

    newItemInput.value = ''
}

function remove(index: number): void {
    const current = [...modelValue.value]
    current.splice(index, 1)
    modelValue.value = current
}
</script>

<template>
    <div class="space-y-3">
        <div v-if="modelValue.length > 0" class="flex flex-wrap gap-2">
            <Badge
                v-for="(item, index) in modelValue"
                :key="item.id ?? item.name"
                variant="secondary"
                class="cursor-pointer"
                @click="remove(index)"
            >
                {{ item.name }}
                <span class="ml-1">&times;</span>
            </Badge>
        </div>

        <Popover v-model:open="open">
            <PopoverTrigger as-child>
                <Button variant="outline" class="w-full justify-start">
                    <Plus class="mr-2 h-4 w-4" />
                    {{ placeholder }}
                </Button>
            </PopoverTrigger>
            <PopoverContent class="w-[300px] p-0" align="start">
                <Command multiple>
                    <CommandInput :placeholder="searchPlaceholder" />
                    <CommandList>
                        <CommandEmpty>
                            <p class="text-sm text-muted-foreground px-2 py-1.5">No results found.</p>
                        </CommandEmpty>
                        <CommandGroup>
                            <CommandItem
                                v-for="option in options"
                                :key="option.id"
                                :value="option.name"
                                @select="toggle(option)"
                            >
                                <Check
                                    class="mr-2 h-4 w-4"
                                    :class="isSelected(option) ? 'opacity-100' : 'opacity-0'"
                                />
                                {{ option.name }}
                            </CommandItem>
                        </CommandGroup>
                    </CommandList>
                </Command>
                <Separator />
                <div class="flex items-center gap-2 p-2">
                    <Input
                        v-model="newItemInput"
                        :placeholder="newItemPlaceholder"
                        class="h-8 text-sm"
                        @keydown.enter.prevent="addNew"
                    />
                    <Button size="sm" variant="secondary" @click="addNew" type="button">
                        Add
                    </Button>
                </div>
            </PopoverContent>
        </Popover>

        <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
    </div>
</template>
