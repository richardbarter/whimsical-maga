<script setup lang="ts">
import { Megaphone, SearchCheck } from "lucide-vue-next";

defineProps<{
  claim: string | null;
  realityCheck: string | null;
}>();
</script>

<template>
  <div
    v-if="claim || realityCheck"
    class="grid gap-4"
    :class="{ 'sm:grid-cols-2': claim && realityCheck }"
  >
    <section v-if="claim" class="rounded-xl border border-primary/25 bg-primary/10 p-6">
      <h2 class="flex items-center gap-2.5 font-bold text-foreground">
        <span class="flex size-8 items-center justify-center rounded-full bg-primary text-primary-foreground">
          <Megaphone class="size-4" aria-hidden="true" />
        </span>
        The claim
      </h2>
      <p class="mt-3 whitespace-pre-line leading-relaxed text-foreground/85">{{ claim }}</p>
    </section>

    <section v-if="realityCheck" class="rounded-xl border border-secondary bg-secondary/30 p-6">
      <h2 class="flex items-center gap-2.5 font-bold text-foreground">
        <span class="flex size-8 items-center justify-center rounded-full bg-secondary-foreground text-secondary">
          <SearchCheck class="size-4" aria-hidden="true" />
        </span>
        Reality check
      </h2>
      <p class="mt-3 whitespace-pre-line leading-relaxed text-foreground/85">{{ realityCheck }}</p>
    </section>
  </div>
</template>
