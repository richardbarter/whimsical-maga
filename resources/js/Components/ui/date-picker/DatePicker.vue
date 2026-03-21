<script setup lang="ts">
import { VueDatePicker } from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";

const model = defineModel<string>({ default: "" });

withDefaults(
  defineProps<{
    placeholder?: string;
  }>(),
  {
    placeholder: "Pick a date",
  },
);
</script>

<template>
  <VueDatePicker
    v-model="model"
    model-type="yyyy-MM-dd"
    :time-config="{ enableTimePicker: false }"
    :placeholder="placeholder"
    auto-apply
    class="w-full"
  />
</template>
