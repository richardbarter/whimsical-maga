<script setup lang="ts">
import type { DateValue } from '@internationalized/date'
import { CalendarDate } from '@internationalized/date'
import { computed, ref, watch } from 'vue'
import { CalendarIcon } from 'lucide-vue-next'
import { Calendar } from '@/Components/ui/calendar'
import { Button } from '@/Components/ui/button'
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover'

const props = withDefaults(defineProps<{
  modelValue?: string
  placeholder?: string
}>(), {
  modelValue: '',
  placeholder: 'Pick a date',
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const open = ref(false)

const calendarDate = computed<DateValue | undefined>(() => {
  if (!props.modelValue) return undefined
  const [year, month, day] = props.modelValue.split('-').map(Number)
  if (!year || !month || !day) return undefined
  return new CalendarDate(year, month, day)
})

const formattedDate = computed(() => {
  if (!calendarDate.value) return ''
  return `${calendarDate.value.month}/${calendarDate.value.day}/${calendarDate.value.year}`
})

function onDateSelect(date: DateValue | undefined) {
  if (!date) return
  const iso = `${date.year}-${String(date.month).padStart(2, '0')}-${String(date.day).padStart(2, '0')}`
  emit('update:modelValue', iso)
  open.value = false
}
</script>

<template>
  <Popover v-model:open="open">
    <PopoverTrigger as-child>
      <Button
        variant="outline"
        class="w-full justify-start text-left font-normal"
        :class="{ 'text-muted-foreground': !calendarDate }"
      >
        <CalendarIcon class="mr-2 h-4 w-4" />
        {{ formattedDate || placeholder }}
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-auto p-0">
      <Calendar
        :model-value="calendarDate"
        @update:model-value="onDateSelect"
      />
    </PopoverContent>
  </Popover>
</template>
