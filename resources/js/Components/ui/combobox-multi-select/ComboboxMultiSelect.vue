<script setup lang="ts">
import { ref, computed } from 'vue'
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

const props = withDefaults(defineProps<{
    modelValue: string[]
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

const emit = defineEmits<{
    'update:modelValue': [value: string[]]
}>()

const open = ref(false)
const newItemInput = ref('')

function isNumericString(val: string): boolean {
    return /^\d+$/.test(val)
}

const selectedLabels = computed(() => {
    return props.modelValue.map(val => {
        if (isNumericString(val)) {
            const option = props.options.find(o => o.id === Number(val))
            return option?.name ?? val
        }
        return val
    })
})

function isSelected(id: number): boolean {
    return props.modelValue.includes(String(id))
}

function toggle(id: number) {
    const idStr = String(id)
    const current = [...props.modelValue]
    const index = current.indexOf(idStr)
    if (index === -1) {
        current.push(idStr)
    } else {
        current.splice(index, 1)
    }
    emit('update:modelValue', current)
}

function addNew() {
    const name = newItemInput.value.trim()
    if (!name) return

    const current = [...props.modelValue]
    const existing = props.options.find(o => o.name.toLowerCase() === name.toLowerCase())

    if (existing) {
        const id = String(existing.id)
        if (!current.includes(id)) {
            current.push(id)
            emit('update:modelValue', current)
        }
    } else if (!current.includes(name)) {
        current.push(name)
        emit('update:modelValue', current)
    }

    newItemInput.value = ''
}

function remove(index: number) {
    const current = [...props.modelValue]
    current.splice(index, 1)
    emit('update:modelValue', current)
}
</script>

<template>
    <div class="space-y-3">
        <div v-if="modelValue.length" class="flex flex-wrap gap-2">
            <Badge
                v-for="(label, index) in selectedLabels"
                :key="index"
                variant="secondary"
                class="cursor-pointer"
                @click="remove(index)"
            >
                {{ label }}
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
                                @select="toggle(option.id)"
                            >
                                <Check
                                    class="mr-2 h-4 w-4"
                                    :class="isSelected(option.id) ? 'opacity-100' : 'opacity-0'"
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
