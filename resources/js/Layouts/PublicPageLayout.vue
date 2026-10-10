<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { ArrowLeft } from "lucide-vue-next";
</script>

<template>
  <div class="min-h-screen bg-background">
    <!-- Public content pages (quote pages, later the Arguments pages). The full-screen home page uses PublicLayout. -->
    <header class="border-b border-border bg-card">
      <div class="mx-auto flex h-16 max-w-3xl items-center justify-between gap-4 px-4 sm:px-6">
        <Link :href="route('home')" class="text-xl font-bold text-primary">
          Whimsical MAGA
        </Link>
        <Link
          :href="route('home')"
          class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
        >
          <ArrowLeft class="size-4" aria-hidden="true" />
          Back to quotes
        </Link>
      </div>
    </header>

    <main class="mx-auto flex max-w-3xl flex-col gap-6 px-4 py-8 sm:px-6 sm:py-12">
      <slot />
    </main>
  </div>
</template>
